-- =============================================================================
-- Cambio 008: viajes de prueba que pasan por dos centros de distribución
-- =============================================================================
-- Para qué: que el asistente tenga trasbordos reales para mostrar. Como en una red logística real,
-- las sucursales se conectan a través de centros: 010 - Sucursal Gran 10 (Córdoba) y
-- 012 - Sucursal Nueva 12 (Buenos Aires).
--
-- Para cada sucursal activa que no es centro, en los próximos 14 días:
--   * un viaje sucursal -> Córdoba y otro Córdoba -> sucursal;
--   * un viaje sucursal -> Buenos Aires y otro Buenos Aires -> sucursal.
-- Los viajes de vuelta salen después de los de ida, así cualquier par de sucursales se puede unir
-- con un trasbordo en un centro. Además, viajes directos entre los dos centros.
--
-- Vehículos y choferes: activos, sin otro viaje ese día, sin motos (son viajes entre provincias) y,
-- si el vehículo pide licencia especial, chofer con licencia C o E. Los viajes salen vacíos.
--
-- Correr una sola vez (si ya hay 100 o más viajes futuros no hace nada).
-- =============================================================================

USE logitrack;

DELIMITER $$

DROP PROCEDURE IF EXISTS tmp_crear_viaje_prueba$$
CREATE PROCEDURE tmp_crear_viaje_prueba(IN p_origen VARCHAR(10), IN p_destino VARCHAR(10), IN p_salida DATETIME)
BEGIN
    DECLARE v_patente VARCHAR(10);
    DECLARE v_especial TINYINT;
    DECLARE v_chofer INT;

    SET v_patente = NULL;
    SELECT ve.vehiculo_patente, tv.tipo_veh_requiere_lic_especial INTO v_patente, v_especial
      FROM vehiculo ve JOIN tipo_vehiculo tv ON tv.tipo_veh_cod = ve.tipo_veh_cod
     WHERE ve.activo = 1 AND tv.tipo_veh_cod <> 'MOTO'
       AND NOT EXISTS (SELECT 1 FROM viaje v WHERE v.vehiculo_patente = ve.vehiculo_patente
                                               AND DATE(v.fecha_salida) = DATE(p_salida))
     ORDER BY RAND() LIMIT 1;

    SET v_chofer = NULL;
    SELECT c.chofer_legajo INTO v_chofer
      FROM chofer c
     WHERE c.activo = 1
       AND (v_especial = 0 OR c.chofer_tipo_licencia IN ('C', 'E'))
       AND NOT EXISTS (SELECT 1 FROM viaje v WHERE v.chofer_legajo = c.chofer_legajo
                                               AND DATE(v.fecha_salida) = DATE(p_salida))
     ORDER BY RAND() LIMIT 1;

    IF v_patente IS NOT NULL AND v_chofer IS NOT NULL THEN
        INSERT INTO viaje (fecha_salida, sucursal_origen_cod, sucursal_destino_cod, vehiculo_patente, chofer_legajo)
        VALUES (p_salida, p_origen, p_destino, v_patente, v_chofer);
    END IF;
END$$

DROP PROCEDURE IF EXISTS tmp_generar_red_prueba$$
CREATE PROCEDURE tmp_generar_red_prueba()
BEGIN
    DECLARE fin INT DEFAULT 0;
    DECLARE v_suc VARCHAR(10);
    DECLARE hora INT;
    DECLARE c CURSOR FOR SELECT sucursal_cod FROM sucursal WHERE activo = 1 AND sucursal_cod NOT IN ('010', '012');
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET fin = 1;

    IF (SELECT COUNT(*) FROM viaje WHERE fecha_salida > NOW()) < 100 THEN
        OPEN c;
        bucle: LOOP
            FETCH c INTO v_suc;
            IF fin THEN LEAVE bucle; END IF;
            SET hora = 6 + FLOOR(RAND() * 12);
            -- Ida a los centros (días 1 a 5) y vuelta (días 3 a 12): siempre después de la ida.
            CALL tmp_crear_viaje_prueba(v_suc, '010', CURDATE() + INTERVAL (1 + FLOOR(RAND() * 5)) DAY + INTERVAL hora HOUR);
            CALL tmp_crear_viaje_prueba('010', v_suc, CURDATE() + INTERVAL (7 + FLOOR(RAND() * 6)) DAY + INTERVAL hora HOUR);
            CALL tmp_crear_viaje_prueba(v_suc, '012', CURDATE() + INTERVAL (1 + FLOOR(RAND() * 5)) DAY + INTERVAL hora HOUR);
            CALL tmp_crear_viaje_prueba('012', v_suc, CURDATE() + INTERVAL (7 + FLOOR(RAND() * 6)) DAY + INTERVAL hora HOUR);
        END LOOP;
        CLOSE c;

        -- Entre los dos centros, día por medio.
        CALL tmp_crear_viaje_prueba('010', '012', CURDATE() + INTERVAL 2 DAY + INTERVAL 8 HOUR);
        CALL tmp_crear_viaje_prueba('012', '010', CURDATE() + INTERVAL 2 DAY + INTERVAL 9 HOUR);
        CALL tmp_crear_viaje_prueba('010', '012', CURDATE() + INTERVAL 6 DAY + INTERVAL 8 HOUR);
        CALL tmp_crear_viaje_prueba('012', '010', CURDATE() + INTERVAL 6 DAY + INTERVAL 9 HOUR);
    END IF;
END$$

DELIMITER ;

CALL tmp_generar_red_prueba();
DROP PROCEDURE tmp_generar_red_prueba;
DROP PROCEDURE tmp_crear_viaje_prueba;

-- Verificación
SELECT COUNT(*) AS viajes_programados FROM vista_viajes_programados;
-- Ejemplo: rutas para un paquete de 5 kg de 046 (Mendoza) a 030 (Corrientes).
CALL procedimiento_buscar_rutas('046', '030', 5);
