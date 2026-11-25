# Seguridad: resumen para la defensa (parte 1)

## Qué es y de qué se encarga

Proteger los datos según la tríada **CIA**:

| | Qué protege | Ejemplo en LogiTrack |
|---|---|---|
| **C**onfidencialidad | Que cada uno vea solo lo que necesita | El operador no puede leer la tabla `usuario` ni la `auditoria` |
| **I**ntegridad | Que nadie cambie lo que no debe | Nadie puede editar ni borrar el historial de estados |
| **D**isponibilidad | Que los datos estén cuando se necesitan | Backups (parte 2) |

Principio central: **mínimo privilegio**. Cada usuario y cada programa tiene solo los permisos
que necesita, ni uno más.

## Los 3 problemas que había (y cómo se resolvieron)

| # | Antes | Riesgo | Ahora |
|---|---|---|---|
| 1 | La app se conectaba como **root sin contraseña** (`ALL PRIVILEGES WITH GRANT OPTION`) | Un error en PHP = acceso total: `DROP DATABASE`, leer `mysql.user` | 3 cuentas de base con roles y GRANT exactos (script 016); root con contraseña |
| 2 | Solo 3 de 22 APIs controlaban el rol | Un operador podía borrar sucursales, provincias, choferes... | Tabla de permisos `config/permisos.php` en todas las APIs y las pantallas |
| 3 | `http://<IP>/LOGITRACK/database/logitrack.sql` se descargaba sin login | 5.001 clientes (DNI, mail, teléfono) y hashes de contraseñas | `.htaccess`: 403 en carpetas internas y archivos .sql/.md |

## Dos barreras (defensa en profundidad)

```
operador1 ──> [1] PHP: config/permisos.php ──> [2] MariaDB: GRANT de rol_operador ──> datos
                  "¿su rol puede?"                  "¿esta cuenta puede?"
```

- **Barrera 1 (aplicación):** `exigir_permiso_crud('sucursales')` al principio de cada API.
  Si el rol no puede → 403 *"tu rol (operador) no tiene permiso"*. Las pantallas usan la misma tabla
  para no mostrar botones inútiles (sucursales en "Solo consulta").
- **Barrera 2 (base):** aunque PHP tuviera un error, la cuenta `logitrack_operador` no tiene el
  permiso → *"DELETE command denied"* (errores 1142/1143/1370, la API los traduce a 403).

## Cuentas y roles (RBAC)

| Rol de base | Cuenta | PHP la usa cuando… |
|---|---|---|
| `rol_login` | `logitrack_login@localhost` | no hay sesión (login) |
| `rol_operador` | `logitrack_operador@localhost` | la sesión es de un operador |
| `rol_admin` (hereda `rol_operador`) | `logitrack_admin@localhost` | la sesión es de un admin |

`config/conexion.php` elige la cuenta según `$_SESSION['rol']`. Las contraseñas son al azar y están
en `config/credenciales.php` (fuera del repo, en `.gitignore`, bloqueado en la web).

**Qué demuestra cada parte de la teoría:**

| Concepto | Dónde |
|---|---|
| Usuario `'user'@'host'` sin comodines | Las 3 cuentas solo entran desde `localhost` |
| Roles y jerarquía | `CREATE ROLE`, `GRANT rol_operador TO rol_admin`, `SET DEFAULT ROLE` |
| Privilegio de objeto | `GRANT SELECT ON logitrack.sucursal TO rol_operador` |
| **Privilegio por columna** | `GRANT UPDATE (nombre, apellido, mail, telefono, localidad_cod) ON cliente`: edita, pero no puede tocar `activo` (no da de baja) |
| Privilegio de sistema | Solo `PROCESS` para `rol_admin` (pantalla Sesiones) |
| EXECUTE sobre procedimientos | Envíos, estados y asignaciones se crean **solo por procedimiento**: no hay INSERT sobre `envio`, `historial_estado` ni `viaje_envio` |
| Separación de funciones (DDL ≠ DML) | Ninguna cuenta de la app tiene CREATE, DROP, ALTER, GRANT OPTION ni ALL. La estructura la cambia solo root |
| Nunca root en la app | root tiene contraseña y la app no la conoce |

**SQL SECURITY DEFINER:** los triggers, procedimientos y vistas corren con los permisos de su dueño
(root@localhost). Por eso:
- el operador puede `CALL procedimiento_cerrar_viaje` sin tener UPDATE sobre todo `envio`
  (hace solo lo que el procedimiento permite, como un `sudo` de un solo comando);
- la auditoría se escribe sola (trigger) aunque nadie tenga INSERT sobre `auditoria`;
- `vista_envios_detalle` se puede consultar teniendo SELECT solo sobre la vista.

**Historial y auditoría inmutables también por permisos:** nadie tiene UPDATE ni DELETE sobre
`historial_estado` ni `auditoria`. El atajo de mantenimiento (`SET @mantenimiento = 1`, tema triggers)
ya no les sirve a las cuentas de la app: la base rechaza el DELETE antes del trigger.

## Qué puede cada rol (config/permisos.php = GRANT del 016)

| | Operador | Admin |
|---|---|---|
| Envíos | ver, alta, editar | + baja y reactivar |
| Historial de estados | ver, alta | igual (nadie edita ni borra) |
| Viajes | ver, alta, editar, cerrar, agregar envíos | + eliminar |
| Asignaciones (viaje_envio) | ver, asignar, quitar | igual |
| Incidentes | ver, alta, editar | + eliminar |
| Clientes y direcciones | ver, alta, editar | + baja y reactivar |
| Sucursales, choferes, vehículos, provincias, localidades, tipos | **solo ver** | todo |
| Usuarios, sesiones, auditoría | — | todo (auditoría: solo lectura) |

## Contraseñas

- **Usuarios de la app:** `password_hash` (bcrypt, con **salt** incluido: dos usuarios con la misma
  contraseña tienen hashes distintos) y `password_verify`. El hash nunca sale de la API.
- **Cuentas de la base:** al azar (`random_bytes`), fuera del repo.
- **root y pma:** con contraseña desde el 25/11/2026. phpMyAdmin pasó a `auth_type = cookie`
  (pide usuario y contraseña) y HeidiSQL pide la de root.

## Otras protecciones que ya estaban

| Ataque | Protección |
|---|---|
| Inyección SQL | Consultas preparadas (`:parametro`); el ORDER BY sale de una lista blanca |
| XSS | Todo dato de la base pasa por `esc()` antes de ir al HTML |
| Robo de sesión | Cookie HttpOnly + SameSite=Strict; id nuevo al hacer login; en la base solo el SHA-256 |
| Mensajes con datos internos | Los errores van al log; al usuario, un mensaje genérico |
| CORS | Sacado el `Access-Control-Allow-Origin: *` (front y API están en el mismo sitio) |

## Demo en vivo (2 minutos)

1. **Barrera 2 sin PHP**, en una consola con la cuenta del operador
   (`mysql -u logitrack_operador -p logitrack`):
   ```sql
   SELECT CURRENT_ROLE();                          -- rol_operador
   DELETE FROM sucursal WHERE sucursal_cod='001';  -- ERROR 1142 DELETE command denied
   UPDATE cliente SET activo = 0 WHERE cliente_dni = 10002166;  -- ERROR 1143 ... column 'activo'
   SELECT * FROM usuario;                          -- ERROR 1142 SELECT command denied
   SET @mantenimiento = 1; DELETE FROM historial_estado WHERE id_historial = 1;  -- ERROR 1142
   DROP TABLE provincia;                           -- ERROR 1142 DROP command denied
   ```
2. **Barrera 1:** entrar como `operador1` → Sucursales muestra "Solo consulta" y Envíos no tiene
   "Dar de baja".
3. **Sesiones (admin) → Conexiones de MariaDB:** las conexiones de la app aparecen como
   `logitrack_admin` / `logitrack_operador`, nunca como root.
4. `SHOW GRANTS FOR rol_operador;` (como root) para mostrar los permisos.
5. `http://localhost/LOGITRACK/database/logitrack.sql` → 403 Forbidden.

## Preguntas probables

- **¿Por qué no alcanza con controlar en PHP?** Porque si PHP tiene un error (o alguien consigue
  ejecutar SQL), la base hace lo que la cuenta tenga permitido. Con root eso es todo.
- **¿Por qué el login tiene su propia cuenta?** Antes del login no se sabe quién es. Esa cuenta solo
  lee 5 columnas de `usuario` y abre la sesión: si alguien rompiera el login, no puede leer clientes.
- **¿Qué pasa si cambio el rol de un usuario?** El trigger de `usuario` cierra sus sesiones; en el
  próximo login PHP usa la cuenta del rol nuevo.
- **¿Por qué no `GRANT ALL ON logitrack.*`?** Incluye DROP, ALTER y GRANT: la app podría destruir la
  estructura. La cátedra lo marca como prohibido para cuentas de aplicación.
- **¿Por qué `FLUSH PRIVILEGES` si no hace falta?** Con `GRANT` y `CREATE USER` el servidor recarga
  solo; `FLUSH` solo hace falta si se editan las tablas `mysql.*` a mano.
- **¿Y SSL?** PHP y MariaDB están en la misma PC: los datos no viajan por la red. En producción, con
  el servidor de base en otra máquina, sí (`REQUIRE SSL` en la cuenta).

## Queda para la parte 2

Límite de intentos en el login, masking y cuenta de auditor externo (vista + GRANT), backups con
`mysqldump --routines --triggers`, calidad de datos.
