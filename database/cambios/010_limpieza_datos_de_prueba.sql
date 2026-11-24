-- 010 - Limpieza de datos de prueba que quedaron fuera de las reglas del 009
--
-- Son datos cargados a mano mientras se probaba el asistente, ANTES de que existieran
-- los triggers del 009. Los triggers controlan lo nuevo, pero no revisan lo ya cargado.
--
-- 1) Envíos 8019, 8104 y 8110 con fecha_envio 2026-11-24 (futura). fecha_envio es el día
--    en que se recibe el paquete; su RECIBIDO es del 23/11, así que pasa a 2026-11-23.
-- 2) Estado 31332 del envío 8110 (EN_SUCURSAL del 22/11) es anterior a su RECIBIDO (23/11)
--    y además el envío todavía no viajó: se borra.
-- 3) Estado 31244 del envío 8019 (EN_TRANSITO del 23/11): su viaje (3001) sale el 24/11,
--    no puede estar en tránsito antes de salir: se borra.
-- 4) Viaje 3001 con llegada real 25/11 (futura): la llegada real queda NULL (no llegó).
--
-- Se corre una sola vez, después del 009.

USE logitrack;

-- Primero se borran los estados: si no, trigger_before_update_envio rechaza el cambio de
-- fecha del 8110 ("no puede ser posterior a su primer estado (22/11)").
START TRANSACTION;

DELETE FROM historial_estado WHERE id_historial IN (31332, 31244);

UPDATE envio SET fecha_envio = '2026-11-23'
 WHERE nro_envio IN (8019, 8104, 8110) AND fecha_envio = '2026-11-24';

UPDATE viaje SET fecha_llegada = NULL WHERE viaje_id = 3001;

COMMIT;

-- Control: las tres consultas tienen que devolver 0
SELECT COUNT(*) AS envios_futuros FROM envio WHERE fecha_envio > CURDATE();
SELECT COUNT(*) AS llegadas_futuras FROM viaje WHERE fecha_llegada > NOW();
SELECT COUNT(*) AS estados_antes_del_envio
  FROM historial_estado h JOIN envio e ON e.nro_envio = h.nro_envio
 WHERE e.nro_envio IN (8019, 8104, 8110) AND DATE(h.fecha_hora) < e.fecha_envio;
-- Nota: los envíos 8015-8018 (junio 2026) tienen el RECIBIDO un día antes de fecha_envio.
-- Son datos viejos, como los de 2024: no se corrigen (ver CLAUDE.md).
