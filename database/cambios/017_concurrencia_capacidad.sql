-- =====================================================================================
-- 017 - Transacciones y concurrencia: la capacidad de un viaje no se puede superar
--       aunque dos operadores asignen envíos al mismo tiempo
-- =====================================================================================
-- QUÉ:
--   * funcion_carga_viaje_bloqueando(viaje): la misma suma de kg que funcion_carga_viaje,
--     pero con LOCK IN SHARE MODE (lectura con bloqueo).
--   * trigger_before_insert_viaje_envio: lee el viaje con FOR UPDATE (el segundo que asigna
--     al mismo viaje ESPERA a que el primero termine) y controla la capacidad con la función
--     nueva. El resto del trigger (fechas, tramos, trasbordo) no cambia.
--   * trigger_before_update_viaje: al cambiar el vehículo, controla la capacidad con la
--     función nueva. El resto no cambia.
--   * funcion_carga_viaje NO cambia: la usan las vistas y los listados, que solo muestran.
--
-- POR QUÉ (probado el 25/11/2026 con dos sesiones a la vez, moto de 50 kg, envíos de 30 kg):
--   1) Asignación directa (pantalla Asignar envío a viaje): cada sesión vio la carga SIN el
--      envío de la otra (todavía sin COMMIT) y las dos pasaron el control -> 60 kg en una moto
--      de 50. Es una ACTUALIZACIÓN PERDIDA ("la última entrada del concierto").
--   2) Camino del asistente, que sí hacía SELECT ... FOR UPDATE: también 60 kg. En REPEATABLE
--      READ cada transacción lee una "foto" (MVCC) que se saca en su PRIMERA lectura; el
--      asistente leía (al crear el envío) antes de bloquear el viaje, así que después del
--      bloqueo seguía viendo la carga vieja.
--   La regla: bloquear primero y leer después, o leer CON bloqueo. Una lectura con bloqueo
--   (FOR UPDATE / LOCK IN SHARE MODE) siempre ve el último dato confirmado, no la foto.
--   Va en la base (no solo en PHP) para que valga para cualquier camino: las pantallas, el
--   asistente y cualquier programa que escriba en la base.
--
-- Comprobar: database/pruebas/demo_concurrencia.sh (antes 60 kg; ahora la segunda se rechaza).
-- =====================================================================================

DELIMITER $$

DROP FUNCTION IF EXISTS funcion_carga_viaje_bloqueando $$
CREATE FUNCTION funcion_carga_viaje_bloqueando(p_viaje_id INT)
RETURNS DECIMAL(10,2)
READS SQL DATA
COMMENT 'Kg cargados en el viaje, leídos CON bloqueo (último dato confirmado). Para controles, no para listados.'
BEGIN
    DECLARE v_carga DECIMAL(10,2);
    SELECT COALESCE(SUM(e.peso_kg), 0) INTO v_carga
      FROM viaje_envio x JOIN envio e ON e.nro_envio = x.nro_envio
     WHERE x.viaje_id = p_viaje_id
      LOCK IN SHARE MODE;
    RETURN v_carga;
END $$

DROP TRIGGER IF EXISTS trigger_before_insert_viaje_envio $$
CREATE TRIGGER trigger_before_insert_viaje_envio
BEFORE INSERT ON viaje_envio
FOR EACH ROW
BEGIN
    DECLARE v_tramo_anterior INT;
    DECLARE v_origen, v_destino_anterior VARCHAR(10);
    DECLARE v_salida, v_llegada_real, v_llegada_anterior DATETIME;
    DECLARE v_capacidad, v_peso DECIMAL(10,2);
    DECLARE v_fecha_envio DATE;
    DECLARE v_activo, v_final TINYINT DEFAULT 0;
    DECLARE v_estado VARCHAR(100);
    DECLARE v_mensaje VARCHAR(255);

    -- 017: FOR UPDATE bloquea la fila del viaje hasta el COMMIT/ROLLBACK de esta transacción.
    -- Si otro está asignando a este mismo viaje, acá se espera a que termine.
    SELECT v.sucursal_origen_cod, v.fecha_salida, v.fecha_llegada, tv.tipo_veh_capacidad_kg
      INTO v_origen, v_salida, v_llegada_real, v_capacidad
      FROM viaje v
      JOIN vehiculo ve      ON ve.vehiculo_patente = v.vehiculo_patente
      JOIN tipo_vehiculo tv ON tv.tipo_veh_cod     = ve.tipo_veh_cod
     WHERE v.viaje_id = NEW.viaje_id
       FOR UPDATE;

    -- El viaje tiene que estar por salir
    IF v_llegada_real IS NOT NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El viaje ya terminó: no se le pueden agregar envíos.';
    END IF;
    IF v_salida <= NOW() THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El viaje ya salió: no se le pueden agregar envíos.';
    END IF;

    -- El envío tiene que estar activo, sin terminar y no ser posterior al viaje
    SELECT peso_kg, fecha_envio, activo INTO v_peso, v_fecha_envio, v_activo
      FROM envio WHERE nro_envio = NEW.nro_envio;
    IF v_activo = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El envío está dado de baja.';
    END IF;
    IF DATE(v_salida) < v_fecha_envio THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El viaje sale antes de la fecha del envío.';
    END IF;
    SET v_estado = funcion_ultimo_estado(NEW.nro_envio);
    SELECT es_final, estado_nombre INTO v_final, v_estado
      FROM estado_envio WHERE estado_cod = v_estado;
    IF v_final = 1 THEN
        SET v_mensaje = CONCAT('El envío ya está "', v_estado, '": no se puede asignar a un viaje.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;

    -- Tramo: 1 = viaje directo, 2 = trasbordo (máximo). El UNIQUE (nro_envio, tramo) impide que
    -- dos asignaciones simultáneas del mismo envío queden las dos como tramo 1.
    SELECT COALESCE(MAX(tramo), 0) INTO v_tramo_anterior FROM viaje_envio WHERE nro_envio = NEW.nro_envio;
    SET NEW.tramo = v_tramo_anterior + 1;
    IF NEW.tramo > 2 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un envío puede tener como máximo un trasbordo (dos viajes).';
    END IF;

    -- Trasbordo: el 2.º viaje sale de donde llega el 1.º y después de su llegada estimada
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

    -- 017: capacidad con la carga leída CON bloqueo (no la "foto" de la transacción)
    IF funcion_carga_viaje_bloqueando(NEW.viaje_id) + v_peso > v_capacidad THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El vehículo del viaje no tiene capacidad para este envío.';
    END IF;
END $$

DROP TRIGGER IF EXISTS trigger_before_update_viaje $$
CREATE TRIGGER trigger_before_update_viaje
BEFORE UPDATE ON viaje
FOR EACH ROW
BEGIN
    DECLARE v_capacidad DECIMAL(10,2);

    IF NEW.fecha_llegada IS NOT NULL AND NEW.fecha_llegada <= NEW.fecha_salida THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha de llegada debe ser posterior a la fecha de salida';
    END IF;
    IF NEW.fecha_llegada IS NOT NULL AND NEW.fecha_llegada > NOW()
       AND (OLD.fecha_llegada IS NULL OR NEW.fecha_llegada <> OLD.fecha_llegada) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La llegada real no puede estar en el futuro.';
    END IF;

    -- Recursos (013): solo si cambian el vehículo, el chofer o el día
    IF NEW.vehiculo_patente <> OLD.vehiculo_patente OR NEW.chofer_legajo <> OLD.chofer_legajo
       OR DATE(NEW.fecha_salida) <> DATE(OLD.fecha_salida) THEN
        CALL procedimiento_validar_recursos_viaje(NEW.viaje_id, NEW.fecha_salida, NEW.vehiculo_patente, NEW.chofer_legajo);
    END IF;

    -- Trasbordos (009): cambiar la salida o la ruta no puede romper un trasbordo que ya existía
    IF NEW.fecha_salida <> OLD.fecha_salida OR NEW.sucursal_origen_cod <> OLD.sucursal_origen_cod
       OR NEW.sucursal_destino_cod <> OLD.sucursal_destino_cod THEN

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

    -- Capacidad al cambiar el vehículo. El UPDATE ya tiene bloqueada esta fila del viaje (y quien
    -- asigna espera ese bloqueo, ver trigger_before_insert_viaje_envio); 017: la carga se lee CON
    -- bloqueo para ver las asignaciones ya confirmadas, no la foto de la transacción.
    IF NEW.vehiculo_patente <> OLD.vehiculo_patente THEN
        SELECT tv.tipo_veh_capacidad_kg INTO v_capacidad
          FROM vehiculo ve JOIN tipo_vehiculo tv ON tv.tipo_veh_cod = ve.tipo_veh_cod
         WHERE ve.vehiculo_patente = NEW.vehiculo_patente;
        IF funcion_carga_viaje_bloqueando(NEW.viaje_id) > v_capacidad THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El vehículo nuevo no tiene capacidad para la carga que ya lleva el viaje.';
        END IF;
    END IF;
END $$

DELIMITER ;

-- PHP también la usa (Viaje::bloquearParaAsignar), con la cuenta de la app (script 016).
GRANT EXECUTE ON FUNCTION logitrack.funcion_carga_viaje_bloqueando TO rol_operador;
