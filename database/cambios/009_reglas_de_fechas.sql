-- =============================================================================
-- Cambio 009: reglas de fechas (coherencia temporal)
-- =============================================================================
-- Problema: se podía registrar un estado con fecha anterior al último estado del envío (el envío
-- quedaba "En sucursal" antes de haber sido "Recibido"), un envío con fecha futura, una llegada real
-- en el futuro, un incidente fuera del período del viaje, etc. Ni la aplicación ni la base lo
-- impedían.
--
-- Las reglas se ponen en la BASE con triggers: así valen para cualquier programa que escriba
-- (la aplicación, HeidiSQL, un script). La aplicación además lo avisa antes en los formularios.
-- La hora de referencia es NOW() de la base.
--
--   HISTORIAL_ESTADO  trigger_before_insert_historial_estado
--     - no en el futuro;
--     - no antes de la fecha del envío;
--     - no antes del último estado registrado del envío;
--     - nada después de un estado final (Entregado, Devuelto).
--   ENVIO             trigger_before_insert_envio (se reemplaza) y trigger_before_update_envio (nuevo)
--     - fecha_envio no puede ser futura;
--     - al editar, no puede quedar después de su primer estado.
--   VIAJE             trigger_before_insert_viaje (nuevo) y trigger_before_update_viaje (se reemplaza)
--     - la llegada real es posterior a la salida y no está en el futuro;
--     - si se cambia la salida, el origen o el destino, no se puede romper un trasbordo que antes
--       era válido;
--     - si se cambia el vehículo, el nuevo tiene que tener capacidad para la carga.
--   VIAJE_ENVIO       trigger_before_insert_viaje_envio (se reemplaza, suma reglas)
--     - el viaje no salió ni terminó; el envío está activo, no está entregado/devuelto y el viaje
--       no sale antes de la fecha del envío.
--   INCIDENTE         trigger_before_insert_incidente / trigger_before_update_incidente (nuevos),
--                     con procedimiento_validar_fecha_incidente
--     - dentro del período del viaje (desde la salida hasta la llegada real) y no en el futuro.
--
-- Datos existentes: los datos de prueba originales se generaron al azar y no cumplen estas reglas
-- (por ejemplo, 15.282 estados anteriores a la fecha de su envío). No se corrigen: los triggers
-- controlan lo que se carga desde ahora.
-- =============================================================================

USE logitrack;

DELIMITER $$

-- ---------------------------------------------------------------- HISTORIAL_ESTADO
DROP TRIGGER IF EXISTS trigger_before_insert_historial_estado$$
CREATE TRIGGER trigger_before_insert_historial_estado
BEFORE INSERT ON historial_estado
FOR EACH ROW
BEGIN
    DECLARE v_fecha_envio DATE;
    DECLARE v_ultima_fecha DATETIME;
    DECLARE v_ultimo_nombre VARCHAR(100);
    DECLARE v_ultimo_final TINYINT DEFAULT 0;
    DECLARE v_mensaje VARCHAR(255);

    IF NEW.fecha_hora > NOW() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha y hora del estado no puede estar en el futuro.';
    END IF;

    SELECT fecha_envio INTO v_fecha_envio FROM envio WHERE nro_envio = NEW.nro_envio;
    IF DATE(NEW.fecha_hora) < v_fecha_envio THEN
        SET v_mensaje = CONCAT('El estado no puede ser anterior a la fecha del envío (',
                               DATE_FORMAT(v_fecha_envio, '%d/%m/%Y'), ').');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;

    SELECT h.fecha_hora, e.estado_nombre, e.es_final
      INTO v_ultima_fecha, v_ultimo_nombre, v_ultimo_final
      FROM historial_estado h JOIN estado_envio e ON e.estado_cod = h.estado
     WHERE h.nro_envio = NEW.nro_envio
     ORDER BY h.fecha_hora DESC, h.id_historial DESC
     LIMIT 1;

    IF v_ultimo_final = 1 THEN
        SET v_mensaje = CONCAT('El envío ya está "', v_ultimo_nombre, '": no admite estados nuevos.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;
    IF NEW.fecha_hora < v_ultima_fecha THEN
        SET v_mensaje = CONCAT('El estado no puede ser anterior al último del envío ("', v_ultimo_nombre,
                               '", ', DATE_FORMAT(v_ultima_fecha, '%d/%m/%Y %H:%i'), ').');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;
END$$

-- ---------------------------------------------------------------- ENVIO
DROP TRIGGER IF EXISTS trigger_before_insert_envio$$
CREATE TRIGGER trigger_before_insert_envio
BEFORE INSERT ON envio
FOR EACH ROW
BEGIN
    IF NEW.peso_kg <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El peso del envío debe ser mayor a cero';
    END IF;
    IF NEW.fecha_envio > CURDATE() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha del envío no puede ser futura.';
    END IF;
END$$

DROP TRIGGER IF EXISTS trigger_before_update_envio$$
CREATE TRIGGER trigger_before_update_envio
BEFORE UPDATE ON envio
FOR EACH ROW
BEGIN
    DECLARE v_primer_estado DATETIME;
    DECLARE v_mensaje VARCHAR(255);

    IF NEW.fecha_envio <> OLD.fecha_envio THEN
        IF NEW.fecha_envio > CURDATE() THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha del envío no puede ser futura.';
        END IF;
        SELECT MIN(fecha_hora) INTO v_primer_estado FROM historial_estado WHERE nro_envio = NEW.nro_envio;
        IF NEW.fecha_envio > DATE(v_primer_estado) THEN
            SET v_mensaje = CONCAT('La fecha del envío no puede ser posterior a su primer estado (',
                                   DATE_FORMAT(v_primer_estado, '%d/%m/%Y'), ').');
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
        END IF;
    END IF;
END$$

-- ---------------------------------------------------------------- VIAJE
DROP TRIGGER IF EXISTS trigger_before_insert_viaje$$
CREATE TRIGGER trigger_before_insert_viaje
BEFORE INSERT ON viaje
FOR EACH ROW
BEGIN
    IF NEW.fecha_llegada IS NOT NULL AND NEW.fecha_llegada <= NEW.fecha_salida THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha de llegada debe ser posterior a la fecha de salida';
    END IF;
    IF NEW.fecha_llegada IS NOT NULL AND NEW.fecha_llegada > NOW() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La llegada real no puede estar en el futuro.';
    END IF;
END$$

DROP TRIGGER IF EXISTS trigger_before_update_viaje$$
CREATE TRIGGER trigger_before_update_viaje
BEFORE UPDATE ON viaje
FOR EACH ROW
BEGIN
    DECLARE v_capacidad, v_carga DECIMAL(10,2);

    IF NEW.fecha_llegada IS NOT NULL AND NEW.fecha_llegada <= NEW.fecha_salida THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha de llegada debe ser posterior a la fecha de salida';
    END IF;
    IF NEW.fecha_llegada IS NOT NULL AND NEW.fecha_llegada > NOW()
       AND (OLD.fecha_llegada IS NULL OR NEW.fecha_llegada <> OLD.fecha_llegada) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La llegada real no puede estar en el futuro.';
    END IF;

    -- Trasbordos: solo se controla que el cambio no rompa una combinación que antes era válida
    -- (los datos históricos tienen combinaciones al azar).
    IF NEW.fecha_salida <> OLD.fecha_salida OR NEW.sucursal_origen_cod <> OLD.sucursal_origen_cod
       OR NEW.sucursal_destino_cod <> OLD.sucursal_destino_cod THEN

        -- Este viaje es el tramo anterior de algún envío: el siguiente tiene que seguir saliendo
        -- de donde llega este y después de su llegada estimada.
        IF EXISTS (SELECT 1
                     FROM viaje_envio a
                     JOIN viaje_envio b ON b.nro_envio = a.nro_envio AND b.tramo = a.tramo + 1
                     JOIN viaje sig     ON sig.viaje_id = b.viaje_id
                    WHERE a.viaje_id = NEW.viaje_id
                      AND sig.sucursal_origen_cod = OLD.sucursal_destino_cod
                      AND sig.fecha_salida >= OLD.fecha_salida + INTERVAL 24 HOUR
                      AND (sig.sucursal_origen_cod <> NEW.sucursal_destino_cod
                           OR sig.fecha_salida < NEW.fecha_salida + INTERVAL 24 HOUR)) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'El cambio rompe un trasbordo: hay envíos que siguen en otro viaje que sale de donde llega este y después de su llegada estimada.';
        END IF;

        -- Este viaje es el tramo siguiente: tiene que salir de donde llega el anterior y después.
        IF EXISTS (SELECT 1
                     FROM viaje_envio b
                     JOIN viaje_envio a ON a.nro_envio = b.nro_envio AND a.tramo = b.tramo - 1
                     JOIN viaje ant     ON ant.viaje_id = a.viaje_id
                    WHERE b.viaje_id = NEW.viaje_id
                      AND ant.sucursal_destino_cod = OLD.sucursal_origen_cod
                      AND OLD.fecha_salida >= ant.llegada_estimada
                      AND (ant.sucursal_destino_cod <> NEW.sucursal_origen_cod
                           OR NEW.fecha_salida < ant.llegada_estimada)) THEN
            SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'El cambio rompe un trasbordo: hay envíos que llegan en otro viaje y este tiene que salir de esa sucursal después de su llegada estimada.';
        END IF;
    END IF;

    -- Cambio de vehículo: el nuevo tiene que poder llevar la carga.
    IF NEW.vehiculo_patente <> OLD.vehiculo_patente THEN
        SELECT tv.tipo_veh_capacidad_kg INTO v_capacidad
          FROM vehiculo ve JOIN tipo_vehiculo tv ON tv.tipo_veh_cod = ve.tipo_veh_cod
         WHERE ve.vehiculo_patente = NEW.vehiculo_patente;
        SELECT COALESCE(SUM(e.peso_kg), 0) INTO v_carga
          FROM viaje_envio x JOIN envio e ON e.nro_envio = x.nro_envio
         WHERE x.viaje_id = NEW.viaje_id;
        IF v_carga > v_capacidad THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El vehículo nuevo no tiene capacidad para la carga que ya lleva el viaje.';
        END IF;
    END IF;
END$$

-- ---------------------------------------------------------------- VIAJE_ENVIO
DROP TRIGGER IF EXISTS trigger_before_insert_viaje_envio$$
CREATE TRIGGER trigger_before_insert_viaje_envio
BEFORE INSERT ON viaje_envio
FOR EACH ROW
BEGIN
    DECLARE v_tramo_anterior INT;
    DECLARE v_origen, v_destino_anterior VARCHAR(10);
    DECLARE v_salida, v_llegada_real, v_llegada_anterior DATETIME;
    DECLARE v_capacidad, v_carga, v_peso DECIMAL(10,2);
    DECLARE v_fecha_envio DATE;
    DECLARE v_activo, v_final TINYINT DEFAULT 0;
    DECLARE v_estado VARCHAR(100);
    DECLARE v_mensaje VARCHAR(255);

    SELECT v.sucursal_origen_cod, v.fecha_salida, v.fecha_llegada, tv.tipo_veh_capacidad_kg
      INTO v_origen, v_salida, v_llegada_real, v_capacidad
      FROM viaje v
      JOIN vehiculo ve      ON ve.vehiculo_patente = v.vehiculo_patente
      JOIN tipo_vehiculo tv ON tv.tipo_veh_cod     = ve.tipo_veh_cod
     WHERE v.viaje_id = NEW.viaje_id;

    -- El viaje todavía no salió.
    IF v_llegada_real IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El viaje ya terminó: no se le pueden agregar envíos.';
    END IF;
    IF v_salida <= NOW() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El viaje ya salió: no se le pueden agregar envíos.';
    END IF;

    -- El envío está activo, no terminó y existe antes de que salga el viaje.
    SELECT peso_kg, fecha_envio, activo INTO v_peso, v_fecha_envio, v_activo
      FROM envio WHERE nro_envio = NEW.nro_envio;
    IF v_activo = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El envío está dado de baja.';
    END IF;
    IF DATE(v_salida) < v_fecha_envio THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El viaje sale antes de la fecha del envío.';
    END IF;
    SELECT e.es_final, e.estado_nombre INTO v_final, v_estado
      FROM historial_estado h JOIN estado_envio e ON e.estado_cod = h.estado
     WHERE h.nro_envio = NEW.nro_envio
     ORDER BY h.fecha_hora DESC, h.id_historial DESC LIMIT 1;
    IF v_final = 1 THEN
        SET v_mensaje = CONCAT('El envío ya está "', v_estado, '": no se puede asignar a un viaje.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;

    -- Tramo: el siguiente al último que tiene el envío (máximo un trasbordo).
    SELECT COALESCE(MAX(tramo), 0) INTO v_tramo_anterior FROM viaje_envio WHERE nro_envio = NEW.nro_envio;
    SET NEW.tramo = v_tramo_anterior + 1;
    IF NEW.tramo > 2 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un envío puede tener como máximo un trasbordo (dos viajes).';
    END IF;

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
    SELECT COALESCE(SUM(e.peso_kg), 0) INTO v_carga
      FROM viaje_envio x JOIN envio e ON e.nro_envio = x.nro_envio
     WHERE x.viaje_id = NEW.viaje_id;
    IF v_carga + v_peso > v_capacidad THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El vehículo del viaje no tiene capacidad para este envío.';
    END IF;
END$$

-- ---------------------------------------------------------------- INCIDENTE
DROP PROCEDURE IF EXISTS procedimiento_validar_fecha_incidente$$
CREATE PROCEDURE procedimiento_validar_fecha_incidente(IN p_viaje_id INT, IN p_fecha DATETIME)
BEGIN
    DECLARE v_salida, v_llegada DATETIME;
    DECLARE v_mensaje VARCHAR(255);

    IF p_fecha > NOW() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha del incidente no puede estar en el futuro.';
    END IF;
    SELECT fecha_salida, fecha_llegada INTO v_salida, v_llegada FROM viaje WHERE viaje_id = p_viaje_id;
    IF p_fecha < v_salida THEN
        SET v_mensaje = CONCAT('El incidente no puede ser anterior a la salida del viaje (',
                               DATE_FORMAT(v_salida, '%d/%m/%Y %H:%i'), ').');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;
    IF v_llegada IS NOT NULL AND p_fecha > v_llegada THEN
        SET v_mensaje = CONCAT('El incidente no puede ser posterior a la llegada del viaje (',
                               DATE_FORMAT(v_llegada, '%d/%m/%Y %H:%i'), ').');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;
END$$

DROP TRIGGER IF EXISTS trigger_before_insert_incidente$$
CREATE TRIGGER trigger_before_insert_incidente
BEFORE INSERT ON incidente
FOR EACH ROW
BEGIN
    CALL procedimiento_validar_fecha_incidente(NEW.viaje_id, NEW.fecha_hora);
END$$

DROP TRIGGER IF EXISTS trigger_before_update_incidente$$
CREATE TRIGGER trigger_before_update_incidente
BEFORE UPDATE ON incidente
FOR EACH ROW
BEGIN
    IF NEW.fecha_hora <> OLD.fecha_hora OR NEW.viaje_id <> OLD.viaje_id THEN
        CALL procedimiento_validar_fecha_incidente(NEW.viaje_id, NEW.fecha_hora);
    END IF;
END$$

DELIMITER ;

-- Verificación: triggers de la base.
SELECT EVENT_OBJECT_TABLE AS tabla, TRIGGER_NAME, ACTION_TIMING, EVENT_MANIPULATION
  FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = 'logitrack' ORDER BY tabla, TRIGGER_NAME;
