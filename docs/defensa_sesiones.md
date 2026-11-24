# Sesiones: resumen para la defensa

## Qué es una sesión

El servidor no recuerda a nadie entre un clic y el siguiente. La sesión es lo que le permite
saber que un pedido viene del mismo usuario que se logueó antes.

| | Sesión de la aplicación | Sesión de la base de datos |
|---|---|---|
| Qué es | Un usuario logueado en LogiTrack | Una conexión abierta a MariaDB |
| Dónde vive | PHP (`$_SESSION`) + tabla `sesion` | El motor (`SHOW PROCESSLIST`) |
| Cuánto dura | Hasta el logout o 30 min sin uso | Depende del programa: LogiTrack abre una por pedido y la cierra al terminar (milisegundos); HeidiSQL la mantiene abierta mientras esté conectado |

Ejemplo: operador1 trabaja 1 hora y hace 200 clics = 1 sesión de aplicación y 200 sesiones de base.

**Sesiones concurrentes** = varias abiertas al mismo tiempo:
- Varias personas a la vez: el riesgo es que dos asignen el último lugar de un viaje. Lo resuelve
  `FOR UPDATE` en el asistente (se explica en transacciones).
- El mismo usuario en dos lugares (PC y celular): **lo permitimos**. Cada login es una fila nueva
  en `sesion`.

## Dónde y cómo lo aplicamos

| Qué | Dónde |
|---|---|
| Un solo control de sesión para la API, las pantallas y `check_session` | `config/session.php` |
| Cookie `HttpOnly` (JS no la lee), `SameSite=Strict` (otro sitio no la manda), `use_strict_mode` (no acepta ids inventados). `Secure` apagado porque XAMPP usa http | `config/session.php` |
| `session_regenerate_id` en el login (evita la fijación de sesión) | `iniciar_sesion()` |
| Tabla `sesion`: usuario, inicio, último acceso, fin, motivo, IP, navegador. Del id de PHP se guarda solo el hash SHA-256 | script 011 |
| El vencimiento lo decide la base: `funcion_minutos_sesion()` (30) y `funcion_sesion_activa(id)` | script 011 |
| `procedimiento_iniciar_sesion` (rechaza usuarios dados de baja), `procedimiento_cerrar_sesion`, `procedimiento_cerrar_sesiones_vencidas` | script 011 |
| `trigger_after_update_usuario`: dar de baja o cambiar el rol cierra las sesiones abiertas | script 011 |
| `vista_sesiones_abiertas` | script 011 |
| Variables de sesión de la base: en cada pedido `SET @usuario_app, @sesion_app` | `modelos/Sesion.php` (`tocar`) |
| Pantalla Sesiones (admin): sesiones abiertas con botón Cerrar, conexiones a la base, historial | `paginas/sesiones/` |
| El login muestra el motivo: vencida, cerrada por un admin, usuario dado de baja, rol cambiado | `login.js` |
| Logout solo por POST | `api/logout.php` |

## Recorrido de un pedido

1. Llega la cookie con el id de sesión. PHP busca `$_SESSION` (usuario, rol, `sesion_id`).
2. `SELECT funcion_sesion_activa(sesion_id)`: ¿la base dice que sigue abierta y se usó hace menos de 30 min?
3. Sí: `UPDATE sesion SET ultimo_acceso = NOW()` y `SET @usuario_app = ...`. Sigue el pedido.
4. No: se borra la sesión de PHP y se responde 401 (API) o se redirige a `login.html?motivo=...` (pantalla).

## Demo en vivo

1. **Dos sesiones de base en HeidiSQL** (dos pestañas de consulta o dos conexiones):
   - `SELECT CONNECTION_ID();` da un número distinto en cada una.
   - En la primera `SET @x = 5; SELECT @x;` da 5. En la segunda `SELECT @x;` da NULL: las variables son de cada sesión.
   - `SELECT @@tx_isolation;` da REPEATABLE-READ. `SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;` lo cambia solo en esa sesión.
2. **Pantalla Sesiones**: "Conexiones a la base" muestra la conexión de HeidiSQL (`Sleep`) y la del propio pedido (`Query`), con sus `@usuario_app` y `@sesion_app`.
3. **Sesiones concurrentes en la app**: entrar con operador1 en dos navegadores → 2 filas en "Sesiones abiertas".
4. **Trigger**: en Gestión de usuarios, desactivar a operador1 → sus 2 sesiones se cierran solas. En su navegador, el próximo clic lo lleva al login con "Tu usuario fue dado de baja".
5. **Vencimiento sin esperar 30 min**: en HeidiSQL,
   `UPDATE sesion SET ultimo_acceso = NOW() - INTERVAL 31 MINUTE WHERE sesion_id = ...;`
   El próximo clic de ese usuario lo lleva al login con "Tu sesión venció".

## Preguntas probables

- **¿Por qué guardar las sesiones en la base si PHP ya las maneja?** Para que la base sepa quién
  está conectado, para cortar una sesión desde afuera (baja, cambio de rol, admin) y para tener
  historial. PHP solo no puede hacer nada de eso.
- **¿Por qué el hash y no el id?** Con el id en claro, cualquiera que lea la tabla podría armar la
  cookie y entrar como ese usuario.
- **¿Por qué una función y no un procedimiento para el vencimiento?** Devuelve un valor y se usa
  dentro de un SELECT (y de la vista); no modifica datos.
- **¿Qué pasa con las sesiones vencidas que nadie volvió a usar?** Quedan abiertas en la tabla
  hasta que alguien inicia sesión o el admin abre la pantalla Sesiones: ahí
  `procedimiento_cerrar_sesiones_vencidas` las cierra con fin = último acceso + 30 min.
  Un EVENT de MariaDB podría hacerlo solo, pero requiere activar `event_scheduler` en `my.ini`.
