# Procedimientos y funciones: resumen para la defensa

## Qué son

| | Procedimiento ("motor") | Función ("calculadora") |
|---|---|---|
| Cómo se usa | `CALL procedimiento_cerrar_viaje(3056, NOW(), @n)` | `SELECT funcion_carga_viaje(3056)` (dentro de SELECT, WHERE, triggers) |
| Devuelve | Nada, uno o varios resultados, o parámetros `OUT` | **Siempre un solo valor** (`RETURNS`) |
| Parámetros | `IN`, `OUT`, `INOUT` | Solo de entrada |
| Puede escribir | Sí: INSERT/UPDATE y `START TRANSACTION ... COMMIT` | No: solo lee (`READS SQL DATA`) |

Pregunta típica: "¿procedimiento o función para usar en un SELECT?" Función. El procedimiento
se llama solo con `CALL`.

Por qué en la base y no en PHP: la regla vive en un solo lugar, sirve para cualquier programa
que use la base (la app, HeidiSQL) y viaja una sola vez por la red.

## Los que tenemos y dónde se usan

| Rutina | Tipo | Qué hace | Lo usa |
|---|---|---|---|
| `procedimiento_crear_envio` | P (IN) | Alta de un envío | Envíos y asistente |
| `procedimiento_asignar_envio_a_viaje` | P (IN) | Asigna un envío a un viaje | Asistente, Detalle de viaje |
| `procedimiento_buscar_rutas` | P (IN, resultset) | Viajes directos y con un trasbordo | Asistente |
| `procedimiento_cerrar_viaje` | P (IN + **OUT**) | Llegada real y envíos "En sucursal", con transacción | Viajes y Detalle (botón **Cerrar viaje**) |
| `procedimiento_registrar_estado_envio` | P (IN + **OUT**) | Agrega un estado al historial | Envíos (**Cambiar estado**), Historial |
| `procedimiento_resumen_por_sucursal` | P (IN, resultset) | Números por sucursal en un período | Pantalla **Resumen por sucursal** |
| `procedimiento_validar_fecha_incidente` | P (IN) | Valida la fecha de un incidente | Triggers de incidente |
| `procedimiento_iniciar_sesion` / `cerrar_sesion` / `cerrar_sesiones_vencidas` | P | Sesiones (tema anterior) | Login, logout |
| `funcion_carga_viaje(viaje)` | F | kg que lleva un viaje | 2 triggers, vista de viajes programados, listado y detalle de viajes |
| `funcion_ultimo_estado(envío)` | F | Código del último estado | Listado de envíos, trigger de viaje_envio, la función siguiente |
| `funcion_envio_terminado(envío)` | F | 1 si está ENTREGADO o DEVUELTO | Cerrar viaje, resumen, envíos pendientes |
| `funcion_sesion_activa` / `funcion_minutos_sesion` | F | Sesiones | Sesiones |

Una función usa a otra: `funcion_envio_terminado` llama a `funcion_ultimo_estado`.

## El ejemplo modelo: cerrar un viaje (script 012)

```sql
DECLARE EXIT HANDLER FOR SQLEXCEPTION
BEGIN
    ROLLBACK;      -- deshace todo
    RESIGNAL;      -- y el error sigue hasta la app con su mensaje original
END;
START TRANSACTION;
SELECT ... FROM viaje WHERE viaje_id = p_viaje_id FOR UPDATE;   -- bloquea el viaje
-- validaciones con SIGNAL: no existe / ya cerrado / todavía no salió
UPDATE viaje SET fecha_llegada = ...;                           -- 1 escritura
INSERT INTO historial_estado ... SELECT ... WHERE funcion_envio_terminado(...) = 0;  -- N escrituras
SET p_envios_actualizados = ROW_COUNT();                        -- parámetro OUT
COMMIT;
```

Arquitectura de la cátedra en un solo ejemplo: la **transacción** es el escudo (todo o nada), el
**procedimiento** el motor, la **función** la calculadora y el **trigger** el notario (el de
historial controla las fechas de cada estado).

### Demo

Antes: `C:\xampp\php\php database\pruebas\datos_de_hoy.php`. Al terminar muestra 3 viajes que
salieron hoy y están en curso, con sus envíos (abajo: A, B y C). Al 25/11/2026 fueron 3055, 3056
y 3057.

1. Viajes → "Solo en curso" → **Cerrar viaje** en A → "N envíos pasaron a En sucursal".
2. ROLLBACK: en Envíos, **Cambiar estado** de un envío del viaje B a Demorado, con la hora actual.
   Después cerrar B con una llegada **una hora antes**. La base responde "Envío ...: el estado no
   puede ser anterior al último..." y **el viaje no queda cerrado**: los otros envíos de B, que
   no tenían problema, tampoco cambiaron. Eso es el todo o nada.
3. Cerrar otra vez A → "ya fue cerrado". Cerrar uno programado → "todavía no salió".

## Otras decisiones que se pueden preguntar

- **Funciones en una vista**: `vista_viajes_programados` usa `funcion_carga_viaje` con
  `ALGORITHM = TEMPTABLE`. Sin eso, como `buscar_rutas` cruza la vista consigo misma, la función
  se calculaba por cada par de viajes: 1,6 s contra 0,13 s (medido).
- **No hacer JOIN con una función**: `JOIN estado_envio ON estado_cod = funcion_ultimo_estado(x)`
  la calcula una vez por cada fila del catálogo (7 veces). Por eso la app trae el código con la
  función y el nombre del estado lo busca en PHP (`Modelo::conNombreDeEstado`).
- **Registrar estado no tiene transacción**: es un solo INSERT, ya es atómico.
- **Mensajes**: los `SIGNAL SQLSTATE '45000'` llegan tal cual al usuario (`config/api.php`).
  Desde el 012, el trigger de historial dice qué envío falló.
- **Datos viejos**: los envíos de 2024 son al azar y muchos nunca terminaron. Por eso "Pendientes
  hoy" da números altos y "Agregar envíos pendientes" mira solo los últimos 30 días.
