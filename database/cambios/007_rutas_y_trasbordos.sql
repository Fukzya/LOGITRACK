-- =============================================================================
-- Cambio 007: ruta de los viajes y trasbordos
-- =============================================================================
-- Hasta ahora un viaje solo tenía fechas, vehículo y chofer; su "ruta" se deducía de los envíos que
-- llevaba. Con trasbordos eso no alcanza: un envío Mendoza -> Corrientes puede ir en un viaje
-- Mendoza -> Córdoba y después en otro Córdoba -> Corrientes, y el primer viaje no va a Corrientes.
--
--   1. viaje.sucursal_origen_cod / sucursal_destino_cod (FK a sucursal): cada viaje es un tramo A -> B.
--   2. viaje.llegada_estimada: columna CALCULADA (VIRTUAL) = salida + 24 h. No se guarda: la base
--      la calcula al leerla, así nunca queda desactualizada si cambia la salida (no hay redundancia).
--   3. viaje_envio.tramo: orden en que el envío recorre sus viajes (1 = directo o primer tramo,
--      2 = después del trasbordo). Máximo un trasbordo.
--   4. trigger_before_insert_viaje_envio: calcula el tramo y rechaza cadenas imposibles
--      (el tramo 2 tiene que salir de donde llega el 1 y después de su llegada estimada) y
--      viajes sin capacidad. trigger_before_delete_viaje_envio: no deja quitar un tramo intermedio.
--   5. vista_viajes_programados: viajes que no salieron, con su ruta y su carga libre.
--   6. procedimiento_buscar_rutas(origen, destino, peso): viajes directos y combinaciones con un
--      trasbordo para un envío.
--
-- Datos existentes: el origen/destino de cada viaje se toma de sus envíos (la sucursal más
-- frecuente) y el tramo, del orden de las fechas de salida. Los datos históricos se generaron al azar
-- y hay envíos en hasta 9 viajes; las reglas nuevas se aplican desde ahora (los triggers controlan
-- solo las asignaciones nuevas).
-- =============================================================================

USE logitrack;

-- ---------------------------------------------------------------- 1 y 2: columnas del viaje
ALTER TABLE viaje
    ADD COLUMN sucursal_origen_cod  VARCHAR(10) NULL AFTER fecha_llegada,
    ADD COLUMN sucursal_destino_cod VARCHAR(10) NULL AFTER sucursal_origen_cod,
    ADD COLUMN llegada_estimada DATETIME AS (fecha_salida + INTERVAL 24 HOUR) VIRTUAL AFTER fecha_salida;

-- Origen = sucursal de origen más frecuente entre sus envíos.
CREATE TEMPORARY TABLE tmp_origen AS
SELECT viaje_id, cod FROM (
    SELECT x.viaje_id, e.sucursal_orig_cod AS cod,
           ROW_NUMBER() OVER (PARTITION BY x.viaje_id ORDER BY COUNT(*) DESC, e.sucursal_orig_cod) AS n
      FROM viaje_envio x JOIN envio e ON e.nro_envio = x.nro_envio
     GROUP BY x.viaje_id, e.sucursal_orig_cod) t
 WHERE n = 1;
UPDATE viaje v JOIN tmp_origen o ON o.viaje_id = v.viaje_id SET v.sucursal_origen_cod = o.cod;

-- Destino = sucursal de destino más frecuente, distinta del origen.
CREATE TEMPORARY TABLE tmp_destino AS
SELECT viaje_id, cod FROM (
    SELECT x.viaje_id, e.sucursal_dest_cod AS cod,
           ROW_NUMBER() OVER (PARTITION BY x.viaje_id ORDER BY COUNT(*) DESC, e.sucursal_dest_cod) AS n
      FROM viaje_envio x
      JOIN envio e      ON e.nro_envio  = x.nro_envio
      JOIN tmp_origen o ON o.viaje_id   = x.viaje_id
     WHERE e.sucursal_dest_cod <> o.cod
     GROUP BY x.viaje_id, e.sucursal_dest_cod) t
 WHERE n = 1;
UPDATE viaje v JOIN tmp_destino d ON d.viaje_id = v.viaje_id SET v.sucursal_destino_cod = d.cod;

-- Viajes sin envíos (o sin un destino distinto): una ruta cualquiera entre sucursales existentes.
UPDATE viaje SET sucursal_origen_cod  = LPAD(1 + viaje_id % 50, 3, '0')        WHERE sucursal_origen_cod IS NULL;
UPDATE viaje SET sucursal_destino_cod = LPAD(1 + (viaje_id + 17) % 50, 3, '0') WHERE sucursal_destino_cod IS NULL;

DROP TEMPORARY TABLE tmp_origen;
DROP TEMPORARY TABLE tmp_destino;

ALTER TABLE viaje
    MODIFY sucursal_origen_cod  VARCHAR(10) NOT NULL,
    MODIFY sucursal_destino_cod VARCHAR(10) NOT NULL,
    ADD CONSTRAINT fk_viaje_sucursal_origen  FOREIGN KEY (sucursal_origen_cod)  REFERENCES sucursal (sucursal_cod),
    ADD CONSTRAINT fk_viaje_sucursal_destino FOREIGN KEY (sucursal_destino_cod) REFERENCES sucursal (sucursal_cod),
    ADD CONSTRAINT ck_viaje_origen_destino CHECK (sucursal_origen_cod <> sucursal_destino_cod);

-- ---------------------------------------------------------------- 3: tramo de cada envío
ALTER TABLE viaje_envio ADD COLUMN tramo TINYINT NOT NULL DEFAULT 1;

CREATE TEMPORARY TABLE tmp_tramo AS
SELECT x.viaje_id, x.nro_envio,
       ROW_NUMBER() OVER (PARTITION BY x.nro_envio ORDER BY v.fecha_salida, x.viaje_id) AS tramo
  FROM viaje_envio x JOIN viaje v ON v.viaje_id = x.viaje_id;
UPDATE viaje_envio x JOIN tmp_tramo t ON t.viaje_id = x.viaje_id AND t.nro_envio = x.nro_envio
   SET x.tramo = t.tramo;
DROP TEMPORARY TABLE tmp_tramo;

ALTER TABLE viaje_envio
    ADD CONSTRAINT uq_viaje_envio_tramo UNIQUE (nro_envio, tramo),
    ADD CONSTRAINT ck_viaje_envio_tramo CHECK (tramo >= 1);

-- ---------------------------------------------------------------- 4: triggers
DELIMITER $$

DROP TRIGGER IF EXISTS trigger_before_insert_viaje_envio$$
CREATE TRIGGER trigger_before_insert_viaje_envio
BEFORE INSERT ON viaje_envio
FOR EACH ROW
BEGIN
    DECLARE v_tramo_anterior INT;
    DECLARE v_origen, v_destino_anterior VARCHAR(10);
    DECLARE v_salida, v_llegada_anterior DATETIME;
    DECLARE v_capacidad, v_carga, v_peso DECIMAL(10,2);
    DECLARE v_mensaje VARCHAR(255);

    -- El tramo no lo elige nadie: es el siguiente al último que tiene el envío.
    SELECT COALESCE(MAX(tramo), 0) INTO v_tramo_anterior FROM viaje_envio WHERE nro_envio = NEW.nro_envio;
    SET NEW.tramo = v_tramo_anterior + 1;
    IF NEW.tramo > 2 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un envío puede tener como máximo un trasbordo (dos viajes).';
    END IF;

    SELECT v.sucursal_origen_cod, v.fecha_salida, tv.tipo_veh_capacidad_kg
      INTO v_origen, v_salida, v_capacidad
      FROM viaje v
      JOIN vehiculo ve      ON ve.vehiculo_patente = v.vehiculo_patente
      JOIN tipo_vehiculo tv ON tv.tipo_veh_cod     = ve.tipo_veh_cod
     WHERE v.viaje_id = NEW.viaje_id;

    -- Trasbordo: sale de donde llegó el viaje anterior y después de su llegada estimada.
    IF v_tramo_anterior > 0 THEN
        SELECT v.sucursal_destino_cod, v.llegada_estimada
          INTO v_destino_anterior, v_llegada_anterior
          FROM viaje_envio x JOIN viaje v ON v.viaje_id = x.viaje_id
         WHERE x.nro_envio = NEW.nro_envio AND x.tramo = v_tramo_anterior;
        IF v_origen <> v_destino_anterior THEN
            SET v_mensaje = CONCAT('El trasbordo tiene que salir de la sucursal ', v_destino_anterior,
                                   ', donde llega el viaje anterior.');
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
        END IF;
        IF v_salida < v_llegada_anterior THEN
            SET v_mensaje = CONCAT('El segundo viaje sale antes de que llegue el primero (llegada estimada: ',
                                   DATE_FORMAT(v_llegada_anterior, '%d/%m %H:%i'), ').');
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
        END IF;
    END IF;

    -- Capacidad del vehículo.
    SELECT peso_kg INTO v_peso FROM envio WHERE nro_envio = NEW.nro_envio;
    SELECT COALESCE(SUM(e.peso_kg), 0) INTO v_carga
      FROM viaje_envio x JOIN envio e ON e.nro_envio = x.nro_envio
     WHERE x.viaje_id = NEW.viaje_id;
    IF v_carga + v_peso > v_capacidad THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El vehículo del viaje no tiene capacidad para este envío.';
    END IF;
END$$

DROP TRIGGER IF EXISTS trigger_before_delete_viaje_envio$$
CREATE TRIGGER trigger_before_delete_viaje_envio
BEFORE DELETE ON viaje_envio
FOR EACH ROW
BEGIN
    IF EXISTS (SELECT 1 FROM viaje_envio WHERE nro_envio = OLD.nro_envio AND tramo > OLD.tramo) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El envío sigue en otro viaje después de este: primero quitá el tramo siguiente.';
    END IF;
END$$

DELIMITER ;

-- ---------------------------------------------------------------- 5: vista
CREATE OR REPLACE VIEW vista_viajes_programados AS
SELECT v.viaje_id, v.fecha_salida, v.llegada_estimada,
       v.sucursal_origen_cod,  so.sucursal_completa AS origen,  so.provincia_cod AS origen_provincia,
       v.sucursal_destino_cod, sd.sucursal_completa AS destino, sd.provincia_cod AS destino_provincia,
       v.vehiculo_patente, CONCAT(ve.marca, ' ', ve.modelo) AS vehiculo, tv.tipo_veh_nombre,
       CONCAT(c.nombre, ' ', c.apellido) AS chofer_nombre,
       tv.tipo_veh_capacidad_kg AS capacidad,
       COALESCE(SUM(e.peso_kg), 0) AS carga,
       tv.tipo_veh_capacidad_kg - COALESCE(SUM(e.peso_kg), 0) AS libre,
       COUNT(e.nro_envio) AS cantidad_envios
  FROM viaje v
  JOIN vehiculo ve      ON ve.vehiculo_patente = v.vehiculo_patente
  JOIN tipo_vehiculo tv ON tv.tipo_veh_cod     = ve.tipo_veh_cod
  JOIN chofer c         ON c.chofer_legajo     = v.chofer_legajo
  JOIN vista_sucursal_ubicacion so ON so.sucursal_cod = v.sucursal_origen_cod
  JOIN vista_sucursal_ubicacion sd ON sd.sucursal_cod = v.sucursal_destino_cod
  LEFT JOIN viaje_envio x ON x.viaje_id  = v.viaje_id
  LEFT JOIN envio e       ON e.nro_envio = x.nro_envio
 WHERE v.fecha_llegada IS NULL AND v.fecha_salida > NOW()
 GROUP BY v.viaje_id;

-- ---------------------------------------------------------------- 6: procedimiento
DELIMITER $$

DROP PROCEDURE IF EXISTS procedimiento_buscar_rutas$$
CREATE PROCEDURE procedimiento_buscar_rutas(
    IN p_sucursal_origen  VARCHAR(10),
    IN p_sucursal_destino VARCHAR(10),
    IN p_peso             DECIMAL(8,2)
)
BEGIN
    DECLARE v_prov_origen, v_prov_destino INT;

    SELECT provincia_cod INTO v_prov_origen  FROM vista_sucursal_ubicacion WHERE sucursal_cod = p_sucursal_origen;
    SELECT provincia_cod INTO v_prov_destino FROM vista_sucursal_ubicacion WHERE sucursal_cod = p_sucursal_destino;

    -- prioridad: 1 directo exacto (mismas sucursales), 2 directo por provincia,
    --            3 trasbordo exacto, 4 trasbordo por provincia. Dentro de cada una, el que llega antes.
    SELECT 'DIRECTO' AS tipo,
           IF(a.sucursal_origen_cod = p_sucursal_origen AND a.sucursal_destino_cod = p_sucursal_destino, 1, 2) AS prioridad,
           a.viaje_id AS viaje1_id, NULL AS viaje2_id, a.llegada_estimada AS llegada_final
      FROM vista_viajes_programados a
     WHERE a.libre >= p_peso
       AND a.origen_provincia = v_prov_origen AND a.destino_provincia = v_prov_destino
    UNION ALL
    SELECT 'TRASBORDO',
           IF(a.sucursal_origen_cod = p_sucursal_origen AND b.sucursal_destino_cod = p_sucursal_destino, 3, 4),
           a.viaje_id, b.viaje_id, b.llegada_estimada
      FROM vista_viajes_programados a
      JOIN vista_viajes_programados b
        ON b.sucursal_origen_cod = a.sucursal_destino_cod     -- el 2.º sale de donde llega el 1.º
       AND b.fecha_salida >= a.llegada_estimada               -- y después de que llegue
     WHERE a.libre >= p_peso AND b.libre >= p_peso
       AND a.origen_provincia  = v_prov_origen
       AND b.destino_provincia = v_prov_destino
       AND a.destino_provincia <> v_prov_destino              -- si el 1.º ya llega, es directo
    ORDER BY prioridad, llegada_final
    LIMIT 40;
END$$

DELIMITER ;

-- ---------------------------------------------------------------- verificación
SELECT COUNT(*) AS viajes, SUM(sucursal_origen_cod = sucursal_destino_cod) AS origen_igual_destino FROM viaje;
SELECT tramo, COUNT(*) FROM viaje_envio GROUP BY tramo;
SELECT COUNT(*) AS programados FROM vista_viajes_programados;
