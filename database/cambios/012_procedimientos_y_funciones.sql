-- 012 - Procedimientos y funciones: cerrar viaje, cambiar estado, resumen por sucursal
--
-- Qué:
--   1. funcion_carga_viaje(viaje): kg que lleva un viaje. Reemplaza el mismo SUM(peso_kg) que
--      estaba copiado en dos triggers, en vista_viajes_programados (la usa procedimiento_buscar_rutas)
--      y en el modelo Viaje.php.
--   2. funcion_ultimo_estado(envio): código del último estado de un envío. Reemplaza la subconsulta
--      "ORDER BY fecha_hora DESC LIMIT 1" del listado de envíos y del trigger de viaje_envio.
--      funcion_envio_terminado(envio): 1 si ese último estado es final (ENTREGADO / DEVUELTO).
--   3. procedimiento_cerrar_viaje(viaje, llegada, OUT envíos actualizados), recreado: valida, pone
--      la llegada real y deja cada envío del viaje "En sucursal" de destino. Todo en una
--      transacción dentro del procedimiento, con SELECT ... FOR UPDATE y EXIT HANDLER (si algo
--      falla se hace ROLLBACK y el error llega al usuario). Antes solo ponía la llegada.
--   4. procedimiento_registrar_estado_envio(envío, sucursal, estado, fecha, observación, OUT id),
--      recreado: admite una fecha (antes siempre NOW()) y da mensajes claros si el envío, la
--      sucursal o el estado no existen (antes llegaba un error técnico de clave foránea).
--   5. procedimiento_resumen_por_sucursal(desde, hasta), recreado: por sucursal, envíos
--      despachados y kg, envíos con destino ahí, entregados, viajes que salieron y envíos que
--      esperan entrega. Antes solo contaba envíos de origen, sin período.
--   6. trigger_before_insert_historial_estado: mismas reglas, pero los mensajes dicen qué envío
--      falló (al cerrar un viaje se registran varios a la vez).
--   7. Datos de demo: 3 viajes que salieron el 24/11/2026 y siguen sin llegada, con 7 envíos
--      nuevos, para poder cerrarlos en la defensa. Todos los viajes de 2026 eran futuros.
--
-- Por qué: es el tema "procedimientos y funciones" del checklist. Procedimiento = el "motor" que
-- hace una operación completa (CALL, puede escribir, transacción). Función = la "calculadora" que
-- devuelve un valor y se usa dentro de SELECT, triggers y procedimientos.
--
-- Las reglas de los triggers no cambian: solo se reemplaza el cálculo repetido por las funciones.
-- Se corre una sola vez, después del 011.

USE logitrack;

DELIMITER $$

-- ---------------------------------------------------------------- 1 y 2: funciones
DROP FUNCTION IF EXISTS funcion_carga_viaje$$
CREATE FUNCTION funcion_carga_viaje(p_viaje_id INT) RETURNS DECIMAL(10,2)
    READS SQL DATA
BEGIN
    DECLARE v_carga DECIMAL(10,2);
    SELECT COALESCE(SUM(e.peso_kg), 0) INTO v_carga
      FROM viaje_envio x JOIN envio e ON e.nro_envio = x.nro_envio
     WHERE x.viaje_id = p_viaje_id;
    RETURN v_carga;
END$$

DROP FUNCTION IF EXISTS funcion_ultimo_estado$$
CREATE FUNCTION funcion_ultimo_estado(p_nro_envio INT) RETURNS VARCHAR(30)
    READS SQL DATA
BEGIN
    DECLARE v_estado VARCHAR(30) DEFAULT NULL;   -- NULL si el envío no tiene estados
    SELECT estado INTO v_estado
      FROM historial_estado
     WHERE nro_envio = p_nro_envio
     ORDER BY fecha_hora DESC, id_historial DESC
     LIMIT 1;
    RETURN v_estado;
END$$

-- 1 si el envío ya terminó (su último estado es final: ENTREGADO o DEVUELTO), 0 si no.
DROP FUNCTION IF EXISTS funcion_envio_terminado$$
CREATE FUNCTION funcion_envio_terminado(p_nro_envio INT) RETURNS TINYINT
    READS SQL DATA
BEGIN
    DECLARE v_estado VARCHAR(30);
    DECLARE v_final TINYINT DEFAULT 0;
    SET v_estado = funcion_ultimo_estado(p_nro_envio);   -- una función usa a la otra
    SELECT es_final INTO v_final FROM estado_envio WHERE estado_cod = v_estado;
    RETURN v_final;
END$$

DELIMITER ;

-- La vista de viajes programados (la usan procedimiento_buscar_rutas y el asistente) calcula la
-- carga con la función: ya no necesita los LEFT JOIN ni el GROUP BY.
-- ALGORITHM = TEMPTABLE: la vista se arma primero (la función se calcula una vez por viaje) y
-- después se usa. Sin eso, MariaDB "mete" la vista dentro de la consulta y, como buscar_rutas
-- cruza la vista consigo misma (trasbordos), la función se calculaba por cada par de viajes:
-- 1,6 s contra 0,13 s (medido).
CREATE OR REPLACE ALGORITHM = TEMPTABLE VIEW vista_viajes_programados AS
SELECT v.viaje_id, v.fecha_salida, v.llegada_estimada,
       v.sucursal_origen_cod,  so.sucursal_completa AS origen,  so.provincia_cod AS origen_provincia,
       v.sucursal_destino_cod, sd.sucursal_completa AS destino, sd.provincia_cod AS destino_provincia,
       v.vehiculo_patente, CONCAT(ve.marca, ' ', ve.modelo) AS vehiculo, tv.tipo_veh_nombre,
       CONCAT(c.nombre, ' ', c.apellido) AS chofer_nombre,
       tv.tipo_veh_capacidad_kg AS capacidad,
       funcion_carga_viaje(v.viaje_id) AS carga,
       tv.tipo_veh_capacidad_kg - funcion_carga_viaje(v.viaje_id) AS libre,
       (SELECT COUNT(*) FROM viaje_envio x WHERE x.viaje_id = v.viaje_id) AS cantidad_envios
  FROM viaje v
  JOIN vehiculo ve      ON ve.vehiculo_patente = v.vehiculo_patente
  JOIN tipo_vehiculo tv ON tv.tipo_veh_cod     = ve.tipo_veh_cod
  JOIN chofer c         ON c.chofer_legajo     = v.chofer_legajo
  JOIN vista_sucursal_ubicacion so ON so.sucursal_cod = v.sucursal_origen_cod
  JOIN vista_sucursal_ubicacion sd ON sd.sucursal_cod = v.sucursal_destino_cod
 WHERE v.fecha_llegada IS NULL AND v.fecha_salida > NOW();

DELIMITER $$

-- ---------------------------------------------------------------- triggers que usan las funciones
-- Mismas reglas que en el 009; solo cambian el cálculo de la carga y el del último estado.
DROP TRIGGER IF EXISTS trigger_before_update_viaje$$
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

    -- Trasbordos: solo se controla que el cambio no rompa una combinación que antes era válida
    -- (los datos históricos tienen combinaciones al azar).
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

    -- Cambio de vehículo: el nuevo tiene que poder llevar la carga.
    IF NEW.vehiculo_patente <> OLD.vehiculo_patente THEN
        SELECT tv.tipo_veh_capacidad_kg INTO v_capacidad
          FROM vehiculo ve JOIN tipo_vehiculo tv ON tv.tipo_veh_cod = ve.tipo_veh_cod
         WHERE ve.vehiculo_patente = NEW.vehiculo_patente;
        IF funcion_carga_viaje(NEW.viaje_id) > v_capacidad THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El vehículo nuevo no tiene capacidad para la carga que ya lleva el viaje.';
        END IF;
    END IF;
END$$

DROP TRIGGER IF EXISTS trigger_before_insert_viaje_envio$$
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
    SET v_estado = funcion_ultimo_estado(NEW.nro_envio);
    SELECT es_final, estado_nombre INTO v_final, v_estado
      FROM estado_envio WHERE estado_cod = v_estado;
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
    IF funcion_carga_viaje(NEW.viaje_id) + v_peso > v_capacidad THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El vehículo del viaje no tiene capacidad para este envío.';
    END IF;
END$$

-- ---------------------------------------------------------------- 3: cerrar viaje
DROP PROCEDURE IF EXISTS procedimiento_cerrar_viaje$$
CREATE PROCEDURE procedimiento_cerrar_viaje(
    IN  p_viaje_id              INT,
    IN  p_fecha_llegada         DATETIME,     -- NULL = ahora
    OUT p_envios_actualizados   INT
)
BEGIN
    DECLARE v_existe   TINYINT DEFAULT 0;
    DECLARE v_salida, v_llegada DATETIME;
    DECLARE v_destino  VARCHAR(10);
    DECLARE v_mensaje  VARCHAR(255);

    -- Cualquier error (de este procedimiento o de un trigger): se deshace todo y el error
    -- sigue hacia la aplicación con su mensaje original (RESIGNAL).
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    IF p_fecha_llegada IS NULL THEN
        SET p_fecha_llegada = NOW();
    END IF;

    START TRANSACTION;

    -- FOR UPDATE: bloquea la fila del viaje hasta el COMMIT. Si dos operadores lo cierran a la
    -- vez, el segundo espera y después ve que ya está cerrado.
    SELECT 1, fecha_salida, fecha_llegada, sucursal_destino_cod
      INTO v_existe, v_salida, v_llegada, v_destino
      FROM viaje WHERE viaje_id = p_viaje_id
       FOR UPDATE;

    IF v_existe = 0 THEN
        SET v_mensaje = CONCAT('El viaje ', p_viaje_id, ' no existe.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;
    IF v_llegada IS NOT NULL THEN
        SET v_mensaje = CONCAT('El viaje ', p_viaje_id, ' ya fue cerrado (llegó el ',
                               DATE_FORMAT(v_llegada, '%d/%m/%Y %H:%i'), ').');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;
    IF v_salida > NOW() THEN
        SET v_mensaje = CONCAT('El viaje ', p_viaje_id, ' todavía no salió (sale el ',
                               DATE_FORMAT(v_salida, '%d/%m/%Y %H:%i'), ').');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;

    -- La llegada posterior a la salida y no futura la controla trigger_before_update_viaje.
    UPDATE viaje SET fecha_llegada = p_fecha_llegada WHERE viaje_id = p_viaje_id;

    -- Cada envío del viaje que no terminó (entregado o devuelto) queda "En sucursal" donde llegó
    -- el viaje. Si el envío seguía en otro viaje, esa sucursal es la del trasbordo.
    -- trigger_before_insert_historial_estado controla las fechas (por ejemplo, que la llegada no
    -- sea anterior al último estado del envío): si rechaza uno, el EXIT HANDLER deshace todo.
    INSERT INTO historial_estado (nro_envio, sucursal_cod, estado, fecha_hora, observacion)
    SELECT x.nro_envio, v_destino, 'EN_SUCURSAL', p_fecha_llegada,
           CONCAT(IF(e.sucursal_dest_cod = v_destino, 'Llegó a la sucursal de destino en el viaje ',
                                                       'Trasbordo: llegó en el viaje '), p_viaje_id)
      FROM viaje_envio x
      JOIN envio e ON e.nro_envio = x.nro_envio
     WHERE x.viaje_id = p_viaje_id
       AND funcion_envio_terminado(x.nro_envio) = 0;

    SET p_envios_actualizados = ROW_COUNT();

    COMMIT;
END$$

-- ---------------------------------------------------------------- 4: registrar estado
DROP PROCEDURE IF EXISTS procedimiento_registrar_estado_envio$$
CREATE PROCEDURE procedimiento_registrar_estado_envio(
    IN  p_nro_envio    INT,
    IN  p_sucursal_cod VARCHAR(10),
    IN  p_estado       VARCHAR(30),
    IN  p_fecha_hora   DATETIME,      -- NULL = ahora
    IN  p_observacion  TEXT,
    OUT p_id_historial INT
)
BEGIN
    DECLARE v_mensaje VARCHAR(255);

    IF NOT EXISTS (SELECT 1 FROM envio WHERE nro_envio = p_nro_envio) THEN
        SET v_mensaje = CONCAT('El envío ', p_nro_envio, ' no existe.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM sucursal WHERE sucursal_cod = p_sucursal_cod) THEN
        SET v_mensaje = CONCAT('La sucursal ', p_sucursal_cod, ' no existe.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM estado_envio WHERE estado_cod = p_estado) THEN
        SET v_mensaje = CONCAT('El estado ', p_estado, ' no existe.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;

    -- Las reglas de fechas (no futura, no antes del último estado, nada después de uno final) las
    -- controla trigger_before_insert_historial_estado. Es un solo INSERT: no hace falta transacción.
    INSERT INTO historial_estado (nro_envio, sucursal_cod, estado, fecha_hora, observacion)
    VALUES (p_nro_envio, p_sucursal_cod, p_estado, COALESCE(p_fecha_hora, NOW()), p_observacion);

    SET p_id_historial = LAST_INSERT_ID();
END$$

-- ---------------------------------------------------------------- 5: resumen por sucursal
DROP PROCEDURE IF EXISTS procedimiento_resumen_por_sucursal$$
CREATE PROCEDURE procedimiento_resumen_por_sucursal(
    IN p_desde DATE,     -- NULL = desde el principio
    IN p_hasta DATE      -- NULL = hasta hoy
)
BEGIN
    SET p_desde = COALESCE(p_desde, '2000-01-01');
    SET p_hasta = COALESCE(p_hasta, CURDATE());
    IF p_desde > p_hasta THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La fecha desde no puede ser posterior a la fecha hasta.';
    END IF;

    SELECT * FROM (
        SELECT s.sucursal_cod, s.sucursal_completa,
               -- envíos recibidos en la sucursal para despachar (es el origen) en el período
               (SELECT COUNT(*) FROM envio e
                 WHERE e.sucursal_orig_cod = s.sucursal_cod AND e.activo = 1
                   AND e.fecha_envio BETWEEN p_desde AND p_hasta) AS despachados,
               (SELECT COALESCE(SUM(e.peso_kg), 0) FROM envio e
                 WHERE e.sucursal_orig_cod = s.sucursal_cod AND e.activo = 1
                   AND e.fecha_envio BETWEEN p_desde AND p_hasta) AS kg_despachados,
               -- envíos del período que tienen esta sucursal como destino
               (SELECT COUNT(*) FROM envio e
                 WHERE e.sucursal_dest_cod = s.sucursal_cod AND e.activo = 1
                   AND e.fecha_envio BETWEEN p_desde AND p_hasta) AS con_destino,
               -- entregas registradas en la sucursal en el período
               (SELECT COUNT(*) FROM historial_estado h
                 WHERE h.sucursal_cod = s.sucursal_cod AND h.estado = 'ENTREGADO'
                   AND h.fecha_hora >= p_desde AND h.fecha_hora < p_hasta + INTERVAL 1 DAY) AS entregados,
               (SELECT COUNT(*) FROM viaje v
                 WHERE v.sucursal_origen_cod = s.sucursal_cod
                   AND v.fecha_salida >= p_desde AND v.fecha_salida < p_hasta + INTERVAL 1 DAY) AS viajes_salidos,
               -- hoy (sin período): envíos con destino acá que todavía no terminaron
               (SELECT COUNT(*) FROM envio e
                 WHERE e.sucursal_dest_cod = s.sucursal_cod AND e.activo = 1
                   AND funcion_envio_terminado(e.nro_envio) = 0) AS pendientes_hoy
          FROM vista_sucursal_ubicacion s
    ) r
    WHERE despachados + con_destino + entregados + viajes_salidos + pendientes_hoy > 0
    ORDER BY despachados DESC, sucursal_cod;
END$$

-- ---------------------------------------------------------------- 6: mensajes del historial
-- Mismas reglas que en el 009; los mensajes ahora dicen de qué envío se trata. Al cerrar un viaje
-- se registran varios envíos juntos y, si uno falla, el operador tiene que saber cuál.
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
        SET v_mensaje = CONCAT('Envío ', NEW.nro_envio, ': el estado no puede ser anterior a la fecha del envío (',
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
        SET v_mensaje = CONCAT('El envío ', NEW.nro_envio, ' ya está "', v_ultimo_nombre, '": no admite estados nuevos.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;
    IF NEW.fecha_hora < v_ultima_fecha THEN
        SET v_mensaje = CONCAT('Envío ', NEW.nro_envio, ': el estado no puede ser anterior al último ("', v_ultimo_nombre,
                               '", ', DATE_FORMAT(v_ultima_fecha, '%d/%m/%Y %H:%i'), ').');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;
END$$

DELIMITER ;

-- ---------------------------------------------------------------- 7: datos de demo
-- 3 viajes que salieron el 24/11/2026 y siguen en curso, con envíos recibidos el 23/11.
-- La base no deja asignar envíos a un viaje que ya salió (trigger de viaje_envio), así que cada
-- viaje se crea con salida futura, se le asignan los envíos con el procedimiento y después se
-- corrige la salida. Por la misma razón, el estado RECIBIDO que pone el trigger con NOW() se lleva
-- al 23/11 (el paquete se recibió antes de que saliera el viaje).

-- Viaje A: Córdoba (010) -> Buenos Aires (012), 3 envíos
INSERT INTO viaje (fecha_salida, sucursal_origen_cod, sucursal_destino_cod, vehiculo_patente, chofer_legajo)
VALUES (NOW() + INTERVAL 1 DAY, '010', '012', 'FT766IJ', 2);
SET @viaje_a = LAST_INSERT_ID();
CALL procedimiento_crear_envio('2026-11-23', 12.50, 'STD', 10009006, 10002166, '010', '012', 6241);
SET @e1 = LAST_INSERT_ID();
CALL procedimiento_crear_envio('2026-11-23', 3.20, 'FRG', 10076379, 10004520, '010', '012', 214);
SET @e2 = LAST_INSERT_ID();
CALL procedimiento_crear_envio('2026-11-23', 25.00, 'STD', 10094031, 10092658, '010', '012', 6092);
SET @e3 = LAST_INSERT_ID();
CALL procedimiento_asignar_envio_a_viaje(@viaje_a, @e1);
CALL procedimiento_asignar_envio_a_viaje(@viaje_a, @e2);
CALL procedimiento_asignar_envio_a_viaje(@viaje_a, @e3);

-- Viaje B: Buenos Aires (012) -> Córdoba (010), 2 envíos
INSERT INTO viaje (fecha_salida, sucursal_origen_cod, sucursal_destino_cod, vehiculo_patente, chofer_legajo)
VALUES (NOW() + INTERVAL 1 DAY, '012', '010', 'GC708GW', 3);
SET @viaje_b = LAST_INSERT_ID();
CALL procedimiento_crear_envio('2026-11-23', 8.00, 'STD', 10161597, 10105607, '012', '010', 4092);
SET @e4 = LAST_INSERT_ID();
CALL procedimiento_crear_envio('2026-11-23', 1.75, 'FRG', 10174518, 10301931, '012', '010', 8461);
SET @e5 = LAST_INSERT_ID();
CALL procedimiento_asignar_envio_a_viaje(@viaje_b, @e4);
CALL procedimiento_asignar_envio_a_viaje(@viaje_b, @e5);

-- Viaje C: Córdoba (010) -> Buenos Aires (012), 2 envíos
INSERT INTO viaje (fecha_salida, sucursal_origen_cod, sucursal_destino_cod, vehiculo_patente, chofer_legajo)
VALUES (NOW() + INTERVAL 1 DAY, '010', '012', 'GD952SI', 4);
SET @viaje_c = LAST_INSERT_ID();
CALL procedimiento_crear_envio('2026-11-23', 40.00, 'STD', 10105607, 10161597, '010', '012', 1808);
SET @e6 = LAST_INSERT_ID();
CALL procedimiento_crear_envio('2026-11-23', 6.30, 'STD', 10301931, 10174518, '010', '012', 654);
SET @e7 = LAST_INSERT_ID();
CALL procedimiento_asignar_envio_a_viaje(@viaje_c, @e6);
CALL procedimiento_asignar_envio_a_viaje(@viaje_c, @e7);

UPDATE historial_estado SET fecha_hora = '2026-11-23 16:00:00',
       observacion = 'Estado inicial registrado automáticamente por trigger'
 WHERE nro_envio IN (@e1, @e2, @e3, @e4, @e5, @e6, @e7) AND estado = 'RECIBIDO';
UPDATE viaje SET fecha_salida = '2026-11-24 08:00:00' WHERE viaje_id = @viaje_a;
UPDATE viaje SET fecha_salida = '2026-11-24 10:00:00' WHERE viaje_id = @viaje_b;
UPDATE viaje SET fecha_salida = '2026-11-24 14:00:00' WHERE viaje_id = @viaje_c;

-- ---------------------------------------------------------------- verificación
SELECT ROUTINE_TYPE, ROUTINE_NAME FROM information_schema.ROUTINES
 WHERE ROUTINE_SCHEMA = 'logitrack' ORDER BY ROUTINE_TYPE, ROUTINE_NAME;
SELECT v.viaje_id, v.fecha_salida, v.sucursal_origen_cod, v.sucursal_destino_cod,
       funcion_carga_viaje(v.viaje_id) AS carga_kg
  FROM viaje v WHERE v.viaje_id IN (@viaje_a, @viaje_b, @viaje_c);
