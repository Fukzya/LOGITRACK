-- =====================================================================================
-- 019 - Limpieza: se cierran los datos históricos de 2024 que quedaron "colgados"
-- =====================================================================================
-- QUÉ:
--   * Los 284 viajes de 2024 que salieron y nunca se cerraron: llegada = salida + 24 h.
--   * Los 4.793 envíos de 2024 que nunca terminaron (último estado En tránsito o En sucursal):
--     se les registra "Entregado" en su sucursal de destino, un día después de su último estado.
--   * Se borra el chofer 201 ("juan lopez", en minúsculas y sin viajes): quedó de una prueba.
--   * Se borran las filas de auditoría que generan estos cierres (son una corrección de datos,
--     no cambios hechos por un usuario de la aplicación).
--
-- POR QUÉ: los datos de 2024 se generaron al azar y muchos quedaron a mitad de camino. En las
-- pantallas aparecían viajes "en curso" desde hace dos años y paquetes "sin moverse hace 600
-- días", mezclados con los datos actuales en Pendientes y en el tablero del menú. Se conservan
-- como historia (8.000 envíos, 3.000 viajes): sirven para que los EXPLAIN y los índices
-- muestren diferencias reales y para que los clientes tengan historial.
--
-- Los cierres pasan por los triggers (llegada posterior a la salida y no futura; estado
-- posterior al último y no futuro). Los 802 envíos de 2024 sin viaje también quedan
-- "Entregado": son datos inventados y no se reconstruye su recorrido.
--
-- Los datos actuales (de unos días antes de hoy a la semana que viene) no van acá: los genera
-- database/pruebas/datos_de_hoy.php, que se puede volver a correr.
-- =====================================================================================

-- 1) Viajes de 2024 sin cerrar
UPDATE viaje
   SET fecha_llegada = fecha_salida + INTERVAL 24 HOUR
 WHERE fecha_llegada IS NULL
   AND fecha_salida < '2025-01-01';

-- 2) Envíos de 2024 sin terminar: "Entregado" en el destino, un día después del último estado.
--    GREATEST: en los datos al azar hay envíos con estados anteriores a su propia fecha (el
--    trigger rechazaba "un día después" si caía antes de la fecha del envío).
INSERT INTO historial_estado (nro_envio, sucursal_cod, estado, fecha_hora, observacion)
SELECT p.nro_envio, e.sucursal_dest_cod, 'ENTREGADO',
       GREATEST(p.fecha_ultimo_estado, e.fecha_envio) + INTERVAL 1 DAY,
       'Cierre de datos históricos (cambio 019)'
  FROM vista_envios_pendientes p
  JOIN envio e ON e.nro_envio = p.nro_envio
 WHERE e.fecha_envio < '2025-01-01';

-- 3) Chofer de prueba
DELETE FROM chofer WHERE chofer_legajo = 201 AND nombre = 'juan' AND apellido = 'lopez';

-- 4) Auditoría generada por estos cierres (las llegadas puestas a los viajes de 2024)
DELETE FROM auditoria
 WHERE tabla = 'viaje'
   AND clave IN (SELECT viaje_id FROM viaje WHERE fecha_salida < '2025-01-01');

-- Comprobar: los dos tienen que dar 0
SELECT COUNT(*) AS viajes_2024_sin_cerrar FROM viaje WHERE fecha_llegada IS NULL AND fecha_salida < '2025-01-01';
SELECT COUNT(*) AS envios_2024_pendientes
  FROM vista_envios_pendientes p JOIN envio e ON e.nro_envio = p.nro_envio
 WHERE e.fecha_envio < '2025-01-01';
