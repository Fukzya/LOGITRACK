-- =============================================================================
-- Cambio 005: viajes de prueba con fecha futura
-- =============================================================================
-- Para qué: el asistente "Nuevo envío" ofrece los viajes que todavía no salieron, y en los datos
-- originales todos los viajes son del pasado. Este script genera 30 viajes repartidos en los
-- próximos 15 días.
--
-- Cómo se arman (para que sean creíbles):
--   * vehículo y chofer activos, sin otro viaje ese mismo día;
--   * si el tipo de vehículo pide licencia especial (camión, semi), el chofer tiene licencia C o E;
--   * 2 de cada 3 viajes ya llevan entre 2 y 5 envíos nuevos, todos con la misma sucursal de
--     origen y destino en una misma provincia: eso define la "ruta" del viaje que muestra el
--     asistente. El resto queda vacío, sin ruta todavía;
--   * los envíos respetan la capacidad del vehículo y la dirección de entrega es del destinatario
--     y está en la provincia de destino;
--   * los envíos se insertan en la tabla y el trigger trigger_after_insert_envio les registra el
--     estado RECIBIDO, igual que un alta normal.
--
-- Correr una sola vez: si ya hay 10 o más viajes futuros no hace nada.
-- =============================================================================

USE logitrack;

DELIMITER $$

DROP PROCEDURE IF EXISTS tmp_generar_viajes_prueba$$
CREATE PROCEDURE tmp_generar_viajes_prueba()
BEGIN
    DECLARE i, j, cantidad, v_chofer, v_viaje, v_prov, v_dir, v_dest, v_rem INT;
    DECLARE v_patente, v_suc_orig, v_suc_dest VARCHAR(10);
    DECLARE v_tipo CHAR(3);
    DECLARE v_cap, v_peso DECIMAL(10,2);
    DECLARE v_especial TINYINT;
    DECLARE v_salida DATETIME;

    IF (SELECT COUNT(*) FROM viaje WHERE fecha_salida > NOW()) < 10 THEN
        SET i = 0;
        WHILE i < 30 DO
            -- Dos viajes por día, entre las 6 y las 17 h.
            SET v_salida = CURDATE() + INTERVAL (1 + FLOOR(i / 2)) DAY
                                     + INTERVAL (6 + FLOOR(RAND() * 12)) HOUR;

            SELECT ve.vehiculo_patente, tv.tipo_veh_capacidad_kg, tv.tipo_veh_requiere_lic_especial
              INTO v_patente, v_cap, v_especial
              FROM vehiculo ve JOIN tipo_vehiculo tv ON tv.tipo_veh_cod = ve.tipo_veh_cod
             WHERE ve.activo = 1
               AND NOT EXISTS (SELECT 1 FROM viaje v
                                WHERE v.vehiculo_patente = ve.vehiculo_patente
                                  AND DATE(v.fecha_salida) = DATE(v_salida))
             ORDER BY RAND() LIMIT 1;

            SELECT c.chofer_legajo INTO v_chofer
              FROM chofer c
             WHERE c.activo = 1
               AND (v_especial = 0 OR c.chofer_tipo_licencia IN ('C', 'E'))
               AND NOT EXISTS (SELECT 1 FROM viaje v
                                WHERE v.chofer_legajo = c.chofer_legajo
                                  AND DATE(v.fecha_salida) = DATE(v_salida))
             ORDER BY RAND() LIMIT 1;

            INSERT INTO viaje (fecha_salida, vehiculo_patente, chofer_legajo)
            VALUES (v_salida, v_patente, v_chofer);
            SET v_viaje = LAST_INSERT_ID();

            IF i % 3 <> 2 THEN
                -- Ruta del viaje: una sucursal de origen y una provincia de destino con sucursales.
                SELECT sucursal_cod INTO v_suc_orig FROM sucursal WHERE activo = 1 ORDER BY RAND() LIMIT 1;

                SELECT l.provincia_cod INTO v_prov
                  FROM sucursal s JOIN localidad l ON l.localidad_cod = s.sucursal_localidad_cod
                 WHERE s.activo = 1 AND s.sucursal_cod <> v_suc_orig
                 ORDER BY RAND() LIMIT 1;

                SET cantidad = 2 + FLOOR(RAND() * 4);
                SET j = 0;
                WHILE j < cantidad DO
                    -- Peso que entra siempre: cada envío como mucho capacidad / cantidad.
                    SET v_peso = ROUND(0.5 + RAND() * (LEAST(30, v_cap / cantidad) - 1), 2);

                    SELECT s.sucursal_cod INTO v_suc_dest
                      FROM sucursal s JOIN localidad l ON l.localidad_cod = s.sucursal_localidad_cod
                     WHERE s.activo = 1 AND l.provincia_cod = v_prov AND s.sucursal_cod <> v_suc_orig
                     ORDER BY RAND() LIMIT 1;

                    -- Destinatario con una dirección propia en esa provincia (si no hay, cualquiera).
                    SET v_dir = NULL;
                    SELECT d.direccion_id, d.cliente_dni INTO v_dir, v_dest
                      FROM direccion d
                      JOIN localidad l ON l.localidad_cod = d.localidad_cod
                      JOIN cliente c   ON c.cliente_dni   = d.cliente_dni
                     WHERE d.activo = 1 AND c.activo = 1 AND l.provincia_cod = v_prov
                     ORDER BY RAND() LIMIT 1;
                    IF v_dir IS NULL THEN
                        SELECT d.direccion_id, d.cliente_dni INTO v_dir, v_dest
                          FROM direccion d JOIN cliente c ON c.cliente_dni = d.cliente_dni
                         WHERE d.activo = 1 AND c.activo = 1
                         ORDER BY RAND() LIMIT 1;
                    END IF;

                    SELECT cliente_dni INTO v_rem FROM cliente
                     WHERE activo = 1 AND cliente_dni <> v_dest ORDER BY RAND() LIMIT 1;
                    SELECT tipo_cont_cod INTO v_tipo FROM tipo_contenido ORDER BY RAND() LIMIT 1;

                    INSERT INTO envio (fecha_envio, peso_kg, tipo_cont_cod, remitente_dni, destinatario_dni,
                                       sucursal_orig_cod, sucursal_dest_cod, direccion_entrega_id)
                    VALUES (CURDATE(), v_peso, v_tipo, v_rem, v_dest, v_suc_orig, v_suc_dest, v_dir);

                    INSERT INTO viaje_envio (viaje_id, nro_envio) VALUES (v_viaje, LAST_INSERT_ID());
                    SET j = j + 1;
                END WHILE;
            END IF;

            SET i = i + 1;
        END WHILE;
    END IF;
END$$

DELIMITER ;

CALL tmp_generar_viajes_prueba();
DROP PROCEDURE tmp_generar_viajes_prueba;

-- Verificación: viajes futuros con su carga y su capacidad.
SELECT v.viaje_id, v.fecha_salida, v.vehiculo_patente, tv.tipo_veh_nombre,
       tv.tipo_veh_capacidad_kg AS capacidad, COALESCE(SUM(e.peso_kg), 0) AS carga, COUNT(e.nro_envio) AS envios
  FROM viaje v
  JOIN vehiculo ve      ON ve.vehiculo_patente = v.vehiculo_patente
  JOIN tipo_vehiculo tv ON tv.tipo_veh_cod     = ve.tipo_veh_cod
  LEFT JOIN viaje_envio x ON x.viaje_id = v.viaje_id
  LEFT JOIN envio e       ON e.nro_envio = x.nro_envio
 WHERE v.fecha_salida > NOW()
 GROUP BY v.viaje_id
 ORDER BY v.fecha_salida;
