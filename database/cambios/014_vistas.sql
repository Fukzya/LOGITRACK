-- 014 - Vistas: las 5 originales corregidas y en uso
--
-- Qué:
--   1. vista_envios_detalle: sucursales con provincia (usa vista_sucursal_ubicacion: una vista que
--      usa otra), dirección de entrega y activo. La usa el listado de Envíos (el último estado
--      lo agrega el modelo solo para la página que muestra: ver la nota en la vista).
--   2. vista_historial_completo: sucursal con provincia, nombre del estado y si es final. La usa
--      el listado del Historial.
--   3. vista_envios_pendientes: envíos activos que no terminaron, con su último estado, dónde
--      están y hace cuántos días no se mueven. Antes buscaba el último estado por id (no por
--      fecha) e incluía envíos dados de baja.
--   4. vista_viajes_en_curso reemplaza a vista_viajes_activos: viajes que ya salieron y no tienen
--      llegada, con origen, destino, carga y horas de atraso. La anterior mezclaba viajes futuros
--      (208), viajes de 2024 nunca cerrados (284) y los que realmente estaban en curso (23).
--   5. vista_clientes_con_envios: con subconsultas en lugar de dos LEFT JOIN a ENVIO. Los dos
--      JOIN se multiplicaban entre sí (7 envíos enviados y 7 recibidos = 49 filas) y había que
--      corregirlo con COUNT(DISTINCT). Mismos números, sin el producto cruzado.
--   Las usan el menú principal (tarjetas) y los filtros "Solo pendientes" / "Solo en curso".
--
-- Por qué: las 5 vistas originales no las usaba el código y tres daban resultados confusos o
-- lentos. Una vista guarda solo la consulta: oculta los JOIN y deja la regla en un solo lugar.
--
-- Se corre una sola vez, después del 013.

USE logitrack;

-- ---------------------------------------------------------------- 1
-- Sin el último estado: con la función adentro, una página de 50 envíos tardaba 550 ms. EXPLAIN
-- muestra que la base ordena todas las filas ("Using filesort") y en el camino calcula la función
-- para los 8.000 envíos. El modelo la calcula después, solo para las 50 filas de la página (110 ms).
CREATE OR REPLACE VIEW vista_envios_detalle AS
SELECT e.nro_envio, e.fecha_envio, e.peso_kg,
       e.tipo_cont_cod, tc.tipo_cont_nombre,
       e.remitente_dni,    CONCAT(rem.nombre, ' ', rem.apellido)   AS remitente_nombre,
       e.destinatario_dni, CONCAT(dest.nombre, ' ', dest.apellido) AS destinatario_nombre,
       e.sucursal_orig_cod, so.sucursal_completa AS sucursal_orig_nombre,
       e.sucursal_dest_cod, sd.sucursal_completa AS sucursal_dest_nombre,
       e.direccion_entrega_id, CONCAT(d.calle_y_numero, ', ', l.localidad_nombre) AS direccion_entrega,
       e.activo
  FROM envio e
  JOIN tipo_contenido tc ON tc.tipo_cont_cod  = e.tipo_cont_cod
  JOIN cliente rem       ON rem.cliente_dni   = e.remitente_dni
  JOIN cliente dest      ON dest.cliente_dni  = e.destinatario_dni
  JOIN vista_sucursal_ubicacion so ON so.sucursal_cod = e.sucursal_orig_cod
  JOIN vista_sucursal_ubicacion sd ON sd.sucursal_cod = e.sucursal_dest_cod
  JOIN direccion d       ON d.direccion_id    = e.direccion_entrega_id
  JOIN localidad l       ON l.localidad_cod   = d.localidad_cod;

-- ---------------------------------------------------------------- 2
-- Sin remitente ni destinatario (la vista original los tenía): la pantalla no los muestra y los
-- JOIN a cliente sumaban 130 ms a cada página (medido). En una vista que usa la app, lo que no se
-- usa no se agrega: cada JOIN se ejecuta siempre, aunque nadie pida esa columna.
CREATE OR REPLACE VIEW vista_historial_completo AS
SELECT h.id_historial, h.nro_envio, h.sucursal_cod, s.sucursal_completa AS sucursal_nombre,
       h.estado, est.estado_nombre, est.es_final, h.fecha_hora, h.observacion
  FROM historial_estado h
  JOIN estado_envio est ON est.estado_cod = h.estado
  JOIN vista_sucursal_ubicacion s ON s.sucursal_cod = h.sucursal_cod;

-- ---------------------------------------------------------------- 3
-- El último estado es la fila más nueva por fecha (y por id si empatan), igual que
-- funcion_ultimo_estado. Acá hace falta la fila entera (fecha y sucursal), no solo el código.
CREATE OR REPLACE VIEW vista_envios_pendientes AS
SELECT e.nro_envio, e.fecha_envio, e.peso_kg,
       CONCAT(rem.nombre, ' ', rem.apellido)   AS remitente,
       CONCAT(dest.nombre, ' ', dest.apellido) AS destinatario,
       e.sucursal_orig_cod, so.sucursal_completa AS origen,
       e.sucursal_dest_cod, sd.sucursal_completa AS destino,
       h.estado AS ultimo_estado_cod, est.estado_nombre AS ultimo_estado,
       h.sucursal_cod AS sucursal_actual_cod, sa.sucursal_completa AS sucursal_actual,
       h.fecha_hora AS fecha_ultimo_estado,
       DATEDIFF(CURDATE(), h.fecha_hora) AS dias_sin_moverse
  FROM envio e
  JOIN historial_estado h ON h.id_historial = (
           SELECT x.id_historial FROM historial_estado x
            WHERE x.nro_envio = e.nro_envio
            ORDER BY x.fecha_hora DESC, x.id_historial DESC LIMIT 1)
  JOIN estado_envio est  ON est.estado_cod  = h.estado
  JOIN cliente rem       ON rem.cliente_dni  = e.remitente_dni
  JOIN cliente dest      ON dest.cliente_dni = e.destinatario_dni
  JOIN vista_sucursal_ubicacion so ON so.sucursal_cod = e.sucursal_orig_cod
  JOIN vista_sucursal_ubicacion sd ON sd.sucursal_cod = e.sucursal_dest_cod
  JOIN vista_sucursal_ubicacion sa ON sa.sucursal_cod = h.sucursal_cod
 WHERE e.activo = 1 AND est.es_final = 0;

-- ---------------------------------------------------------------- 4
DROP VIEW IF EXISTS vista_viajes_activos;
CREATE OR REPLACE VIEW vista_viajes_en_curso AS
SELECT v.viaje_id, v.fecha_salida, v.llegada_estimada,
       v.sucursal_origen_cod,  so.sucursal_completa AS origen,
       v.sucursal_destino_cod, sd.sucursal_completa AS destino,
       v.vehiculo_patente, CONCAT(ve.marca, ' ', ve.modelo) AS vehiculo,
       v.chofer_legajo, CONCAT(c.nombre, ' ', c.apellido) AS chofer_nombre,
       tv.tipo_veh_capacidad_kg AS capacidad, funcion_carga_viaje(v.viaje_id) AS carga,
       (SELECT COUNT(*) FROM viaje_envio x WHERE x.viaje_id = v.viaje_id) AS cantidad_envios,
       GREATEST(0, TIMESTAMPDIFF(HOUR, v.llegada_estimada, NOW())) AS horas_atraso
  FROM viaje v
  JOIN vehiculo ve      ON ve.vehiculo_patente = v.vehiculo_patente
  JOIN tipo_vehiculo tv ON tv.tipo_veh_cod     = ve.tipo_veh_cod
  JOIN chofer c         ON c.chofer_legajo     = v.chofer_legajo
  JOIN vista_sucursal_ubicacion so ON so.sucursal_cod = v.sucursal_origen_cod
  JOIN vista_sucursal_ubicacion sd ON sd.sucursal_cod = v.sucursal_destino_cod
 WHERE v.fecha_llegada IS NULL AND v.fecha_salida <= NOW();

-- ---------------------------------------------------------------- 5
-- Reporte de solo lectura. Cada subconsulta usa su índice (idx_envio_remitente,
-- idx_envio_destinatario).
CREATE OR REPLACE VIEW vista_clientes_con_envios AS
SELECT c.cliente_dni, CONCAT(c.nombre, ' ', c.apellido) AS nombre_completo, c.mail, c.telefono,
       l.localidad_nombre, p.provincia_nombre, c.activo,
       (SELECT COUNT(*) FROM envio e WHERE e.remitente_dni    = c.cliente_dni) AS cantidad_envios_como_remitente,
       (SELECT COUNT(*) FROM envio e WHERE e.destinatario_dni = c.cliente_dni) AS cantidad_envios_como_destinatario,
       (SELECT COUNT(*) FROM envio e WHERE e.remitente_dni    = c.cliente_dni)
     + (SELECT COUNT(*) FROM envio e WHERE e.destinatario_dni = c.cliente_dni) AS total_envios
  FROM cliente c
  JOIN localidad l ON l.localidad_cod = c.localidad_cod
  JOIN provincia p ON p.provincia_cod = l.provincia_cod;

-- ---------------------------------------------------------------- verificación
SELECT TABLE_NAME AS vista, IS_UPDATABLE AS actualizable
  FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'logitrack' ORDER BY TABLE_NAME;
SELECT SUM(cantidad_envios_como_remitente) AS remitente, SUM(cantidad_envios_como_destinatario) AS destinatario,
       SUM(total_envios) AS total, MAX(total_envios) AS maximo   -- antes: 8089, 8089, 16178, 14
  FROM vista_clientes_con_envios;
SELECT (SELECT COUNT(*) FROM vista_envios_detalle) AS envios,          -- antes: 8089
       (SELECT COUNT(*) FROM vista_historial_completo) AS historial,   -- antes: 31300
       (SELECT COUNT(*) FROM vista_envios_pendientes) AS pendientes,   -- antes: 4882 (con 3 dados de baja)
       (SELECT COUNT(*) FROM vista_viajes_en_curso) AS en_curso;
