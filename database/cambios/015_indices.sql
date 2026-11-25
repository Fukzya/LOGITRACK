-- 015 - Índices para volumen (prueba con 100.000 envíos)
--
-- Qué: tres índices. No cambian ningún resultado, solo la velocidad (se deshacen con DROP INDEX).
--   1. historial_estado (nro_envio, fecha_hora, id_historial): el último estado de un envío
--      (funcion_ultimo_estado, la vista de pendientes, los triggers) busca por envío y ordena
--      por fecha. Con el índice viejo (solo nro_envio) EXPLAIN decía "Using filesort": traía las
--      filas y las ordenaba. Con este, las filas ya vienen ordenadas y lee una sola.
--   2. envio (fecha_envio): filtro por fecha de Envíos, tablero ("recibidos este mes"), resumen.
--   3. viaje (fecha_salida): viajes programados (fecha_salida > NOW()), próximos 7 días, en curso.
--
-- Por qué: en una copia con 100.000 clientes y 100.000 envíos (database/pruebas/carga_100k.sql,
-- medido con database/pruebas/medir_consultas.sh) el filtro por fecha pasó de 368 a 182 ms y el
-- tablero de 3,2 a 1,8 s. Lo que más cambió fue la memoria de MariaDB (my.ini de XAMPP,
-- innodb_buffer_pool_size de 16 MB a 2 GB): la primera página de Envíos pasó de 31 s a 1,9 s.
--
-- Se corre una sola vez, después del 014.

USE logitrack;

CREATE INDEX idx_historial_envio_fecha ON historial_estado (nro_envio, fecha_hora, id_historial);
CREATE INDEX idx_envio_fecha ON envio (fecha_envio);
CREATE INDEX idx_viaje_salida ON viaje (fecha_salida);

-- Verificación: ya no ordena ("Using filesort" desaparece de Extra).
EXPLAIN SELECT estado FROM historial_estado WHERE nro_envio = 8113
         ORDER BY fecha_hora DESC, id_historial DESC LIMIT 1;
