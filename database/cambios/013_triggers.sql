-- 013 - Triggers: historial inmutable, auditoría y reglas del asistente en la base
--
-- Qué:
--   1. Historial de estados inmutable en la base: trigger_before_update_historial_estado y
--      trigger_before_delete_historial_estado lo rechazan. Única excepción: el modo mantenimiento
--      (SET @mantenimiento = 1 en esa conexión), para limpiar datos de prueba. La app nunca lo usa.
--   2. Tabla AUDITORIA y procedimiento_auditar: una fila por cada columna que cambió, con el
--      valor anterior, el nuevo, quién lo hizo (@usuario_app, que la app fija en cada pedido; si el
--      cambio viene de fuera de la app, CURRENT_USER()) y cuándo.
--   3. Triggers AFTER de auditoría en ENVIO, VIAJE (UPDATE y DELETE) y USUARIO.
--   4. Reglas que solo controlaba el asistente, ahora en triggers BEFORE:
--        viaje: vehículo y chofer activos, licencia C o E si el vehículo la pide, vehículo y
--               chofer sin otro viaje ese día;
--        envío: la dirección de entrega es una dirección activa del destinatario.
--      Se controlan en el alta y cuando la edición toca esas columnas (los datos de prueba de 2024
--      no las cumplen y se tienen que poder seguir editando en otros campos).
--   5. REDIRIGIDO (trigger_after_update_envio) se registra en la sucursal donde está el paquete
--      (la de su último estado), no en el destino nuevo.
--
-- Por qué: desde HeidiSQL se podía modificar el historial; no quedaba registro de quién cambiaba
-- un envío o un viaje; y desde las pantallas Viajes y Envíos se podía cargar un camión con un chofer
-- de licencia B o un envío con la dirección de otra persona (hay 7 así en 2026).
-- BEFORE = el portero (valida antes de escribir). AFTER = el notario (registra lo que ya pasó:
-- si la auditoría fuera BEFORE y el cambio fallara después, quedaría registrado algo que no ocurrió).
--
-- Se corre una sola vez, después del 012.

USE logitrack;

-- ---------------------------------------------------------------- 2: tabla de auditoría
CREATE TABLE auditoria (
    auditoria_id    INT AUTO_INCREMENT PRIMARY KEY,
    tabla           VARCHAR(30)  NOT NULL,
    clave           VARCHAR(30)  NOT NULL,          -- n.º de envío, id de viaje, id de usuario
    operacion       ENUM('UPDATE','DELETE') NOT NULL,
    campo           VARCHAR(40)  NOT NULL,          -- '*' en un DELETE (se borró la fila entera)
    valor_anterior  VARCHAR(255) NULL,
    valor_nuevo     VARCHAR(255) NULL,
    usuario         VARCHAR(100) NOT NULL,
    fecha_hora      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_auditoria_registro (tabla, clave),
    INDEX idx_auditoria_fecha (fecha_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DELIMITER $$

-- Registra un cambio solo si el valor cambió. <=> compara también con NULL
-- (NULL = NULL da NULL, en cambio NULL <=> NULL da 1).
DROP PROCEDURE IF EXISTS procedimiento_auditar$$
CREATE PROCEDURE procedimiento_auditar(
    IN p_tabla VARCHAR(30), IN p_clave VARCHAR(30), IN p_operacion VARCHAR(10),
    IN p_campo VARCHAR(40), IN p_anterior VARCHAR(255), IN p_nuevo VARCHAR(255)
)
BEGIN
    IF p_operacion = 'DELETE' OR NOT (p_anterior <=> p_nuevo) THEN
        INSERT INTO auditoria (tabla, clave, operacion, campo, valor_anterior, valor_nuevo, usuario)
        VALUES (p_tabla, p_clave, p_operacion, p_campo, p_anterior, p_nuevo,
                COALESCE(@usuario_app, CURRENT_USER()));
    END IF;
END$$

-- ---------------------------------------------------------------- 1: historial inmutable
DROP TRIGGER IF EXISTS trigger_before_update_historial_estado$$
CREATE TRIGGER trigger_before_update_historial_estado
BEFORE UPDATE ON historial_estado
FOR EACH ROW
BEGIN
    IF COALESCE(@mantenimiento, 0) <> 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El historial de estados no se modifica: para cambiar el estado de un envío, registrá un estado nuevo.';
    END IF;
END$$

DROP TRIGGER IF EXISTS trigger_before_delete_historial_estado$$
CREATE TRIGGER trigger_before_delete_historial_estado
BEFORE DELETE ON historial_estado
FOR EACH ROW
BEGIN
    IF COALESCE(@mantenimiento, 0) <> 1 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El historial de estados no se borra: es el registro de todo el recorrido del envío.';
    END IF;
END$$

-- ---------------------------------------------------------------- 4: reglas de VIAJE
-- Lo usan los triggers BEFORE INSERT y BEFORE UPDATE de viaje.
DROP PROCEDURE IF EXISTS procedimiento_validar_recursos_viaje$$
CREATE PROCEDURE procedimiento_validar_recursos_viaje(
    IN p_viaje_id INT, IN p_salida DATETIME, IN p_patente VARCHAR(10), IN p_legajo INT
)
BEGIN
    DECLARE v_vehiculo_activo, v_chofer_activo, v_especial TINYINT DEFAULT 0;
    DECLARE v_licencia VARCHAR(5);
    DECLARE v_mensaje VARCHAR(255);

    SELECT ve.activo, tv.tipo_veh_requiere_lic_especial INTO v_vehiculo_activo, v_especial
      FROM vehiculo ve JOIN tipo_vehiculo tv ON tv.tipo_veh_cod = ve.tipo_veh_cod
     WHERE ve.vehiculo_patente = p_patente;
    SELECT activo, chofer_tipo_licencia INTO v_chofer_activo, v_licencia
      FROM chofer WHERE chofer_legajo = p_legajo;

    -- Si no existen, la FK da el error al escribir.
    IF v_vehiculo_activo = 0 AND p_patente IN (SELECT vehiculo_patente FROM vehiculo) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El vehículo está dado de baja.';
    END IF;
    IF v_chofer_activo = 0 AND p_legajo IN (SELECT chofer_legajo FROM chofer) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El chofer está dado de baja.';
    END IF;
    IF v_especial = 1 AND v_licencia NOT IN ('C', 'E') THEN
        SET v_mensaje = CONCAT('Ese vehículo pide licencia C o E y el chofer tiene licencia ', v_licencia, '.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;
    IF EXISTS (SELECT 1 FROM viaje WHERE vehiculo_patente = p_patente AND viaje_id <> p_viaje_id
                                     AND DATE(fecha_salida) = DATE(p_salida)) THEN
        SET v_mensaje = CONCAT('El vehículo ', p_patente, ' ya tiene otro viaje el ', DATE_FORMAT(p_salida, '%d/%m/%Y'), '.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;
    IF EXISTS (SELECT 1 FROM viaje WHERE chofer_legajo = p_legajo AND viaje_id <> p_viaje_id
                                     AND DATE(fecha_salida) = DATE(p_salida)) THEN
        SET v_mensaje = CONCAT('El chofer ya tiene otro viaje el ', DATE_FORMAT(p_salida, '%d/%m/%Y'), '.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;
END$$

-- Mismas reglas del 009 más los recursos.
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
    CALL procedimiento_validar_recursos_viaje(0, NEW.fecha_salida, NEW.vehiculo_patente, NEW.chofer_legajo);
END$$

-- Mismas reglas del 012 más los recursos, solo si cambia el vehículo, el chofer o el día.
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

    IF NEW.vehiculo_patente <> OLD.vehiculo_patente OR NEW.chofer_legajo <> OLD.chofer_legajo
       OR DATE(NEW.fecha_salida) <> DATE(OLD.fecha_salida) THEN
        CALL procedimiento_validar_recursos_viaje(NEW.viaje_id, NEW.fecha_salida, NEW.vehiculo_patente, NEW.chofer_legajo);
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

-- ---------------------------------------------------------------- 4: reglas de ENVIO
DROP PROCEDURE IF EXISTS procedimiento_validar_direccion_envio$$
CREATE PROCEDURE procedimiento_validar_direccion_envio(IN p_direccion_id INT, IN p_destinatario INT)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM direccion
                    WHERE direccion_id = p_direccion_id AND cliente_dni = p_destinatario AND activo = 1)
       AND p_direccion_id IN (SELECT direccion_id FROM direccion) THEN   -- si no existe, lo dice la FK
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'La dirección de entrega tiene que ser una dirección activa del destinatario.';
    END IF;
END$$

-- Mismas reglas del 009 más la dirección.
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
    CALL procedimiento_validar_direccion_envio(NEW.direccion_entrega_id, NEW.destinatario_dni);
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
    IF NEW.peso_kg <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El peso del envío debe ser mayor a cero';
    END IF;
    IF NEW.direccion_entrega_id <> OLD.direccion_entrega_id OR NEW.destinatario_dni <> OLD.destinatario_dni THEN
        CALL procedimiento_validar_direccion_envio(NEW.direccion_entrega_id, NEW.destinatario_dni);
    END IF;
END$$

-- ---------------------------------------------------------------- 3 y 5: AFTER de ENVIO
DROP TRIGGER IF EXISTS trigger_after_update_envio$$
CREATE TRIGGER trigger_after_update_envio
AFTER UPDATE ON envio
FOR EACH ROW
BEGIN
    DECLARE v_sucursal_actual VARCHAR(10);

    -- Auditoría: una fila por columna que cambió.
    CALL procedimiento_auditar('envio', NEW.nro_envio, 'UPDATE', 'fecha_envio', OLD.fecha_envio, NEW.fecha_envio);
    CALL procedimiento_auditar('envio', NEW.nro_envio, 'UPDATE', 'peso_kg', OLD.peso_kg, NEW.peso_kg);
    CALL procedimiento_auditar('envio', NEW.nro_envio, 'UPDATE', 'tipo_cont_cod', OLD.tipo_cont_cod, NEW.tipo_cont_cod);
    CALL procedimiento_auditar('envio', NEW.nro_envio, 'UPDATE', 'remitente_dni', OLD.remitente_dni, NEW.remitente_dni);
    CALL procedimiento_auditar('envio', NEW.nro_envio, 'UPDATE', 'destinatario_dni', OLD.destinatario_dni, NEW.destinatario_dni);
    CALL procedimiento_auditar('envio', NEW.nro_envio, 'UPDATE', 'sucursal_orig_cod', OLD.sucursal_orig_cod, NEW.sucursal_orig_cod);
    CALL procedimiento_auditar('envio', NEW.nro_envio, 'UPDATE', 'sucursal_dest_cod', OLD.sucursal_dest_cod, NEW.sucursal_dest_cod);
    CALL procedimiento_auditar('envio', NEW.nro_envio, 'UPDATE', 'direccion_entrega_id', OLD.direccion_entrega_id, NEW.direccion_entrega_id);
    CALL procedimiento_auditar('envio', NEW.nro_envio, 'UPDATE', 'activo', OLD.activo, NEW.activo);

    -- Cambio de destino: estado REDIRIGIDO en la sucursal donde está el paquete (la de su último
    -- estado). Si el envío ya terminó, el trigger de historial lo rechaza y el cambio no se hace.
    IF NEW.sucursal_dest_cod <> OLD.sucursal_dest_cod THEN
        SELECT sucursal_cod INTO v_sucursal_actual
          FROM historial_estado WHERE nro_envio = NEW.nro_envio
         ORDER BY fecha_hora DESC, id_historial DESC LIMIT 1;
        INSERT INTO historial_estado (nro_envio, sucursal_cod, estado, fecha_hora, observacion)
        VALUES (NEW.nro_envio, COALESCE(v_sucursal_actual, NEW.sucursal_orig_cod), 'REDIRIGIDO', NOW(),
                CONCAT('Destino cambiado de la sucursal ', OLD.sucursal_dest_cod, ' a la ', NEW.sucursal_dest_cod));
    END IF;
END$$

-- ---------------------------------------------------------------- 3: AFTER de VIAJE
DROP TRIGGER IF EXISTS trigger_after_update_viaje$$
CREATE TRIGGER trigger_after_update_viaje
AFTER UPDATE ON viaje
FOR EACH ROW
BEGIN
    CALL procedimiento_auditar('viaje', NEW.viaje_id, 'UPDATE', 'fecha_salida', OLD.fecha_salida, NEW.fecha_salida);
    CALL procedimiento_auditar('viaje', NEW.viaje_id, 'UPDATE', 'fecha_llegada', OLD.fecha_llegada, NEW.fecha_llegada);
    CALL procedimiento_auditar('viaje', NEW.viaje_id, 'UPDATE', 'sucursal_origen_cod', OLD.sucursal_origen_cod, NEW.sucursal_origen_cod);
    CALL procedimiento_auditar('viaje', NEW.viaje_id, 'UPDATE', 'sucursal_destino_cod', OLD.sucursal_destino_cod, NEW.sucursal_destino_cod);
    CALL procedimiento_auditar('viaje', NEW.viaje_id, 'UPDATE', 'vehiculo_patente', OLD.vehiculo_patente, NEW.vehiculo_patente);
    CALL procedimiento_auditar('viaje', NEW.viaje_id, 'UPDATE', 'chofer_legajo', OLD.chofer_legajo, NEW.chofer_legajo);
END$$

-- Baja física: queda registrado qué viaje era.
DROP TRIGGER IF EXISTS trigger_after_delete_viaje$$
CREATE TRIGGER trigger_after_delete_viaje
AFTER DELETE ON viaje
FOR EACH ROW
BEGIN
    CALL procedimiento_auditar('viaje', OLD.viaje_id, 'DELETE', '*',
         CONCAT('Salida ', DATE_FORMAT(OLD.fecha_salida, '%d/%m/%Y %H:%i'), ', ', OLD.sucursal_origen_cod, ' -> ',
                OLD.sucursal_destino_cod, ', vehículo ', OLD.vehiculo_patente, ', chofer ', OLD.chofer_legajo),
         NULL);
END$$

-- ---------------------------------------------------------------- 3: AFTER de USUARIO
-- Lo del 011 (cerrar sesiones) más la auditoría. La contraseña no se guarda: solo que cambió.
DROP TRIGGER IF EXISTS trigger_after_update_usuario$$
CREATE TRIGGER trigger_after_update_usuario
AFTER UPDATE ON usuario
FOR EACH ROW
BEGIN
    CALL procedimiento_auditar('usuario', NEW.usuario_id, 'UPDATE', 'username', OLD.username, NEW.username);
    CALL procedimiento_auditar('usuario', NEW.usuario_id, 'UPDATE', 'rol', OLD.rol, NEW.rol);
    CALL procedimiento_auditar('usuario', NEW.usuario_id, 'UPDATE', 'activo', OLD.activo, NEW.activo);
    IF NOT (OLD.password_hash <=> NEW.password_hash) THEN
        CALL procedimiento_auditar('usuario', NEW.usuario_id, 'UPDATE', 'contraseña', '(anterior)', '(cambiada)');
    END IF;

    IF OLD.activo = 1 AND NEW.activo = 0 THEN
        UPDATE sesion SET fin = NOW(), motivo_cierre = 'BAJA_USUARIO'
         WHERE usuario_id = NEW.usuario_id AND fin IS NULL;
    ELSEIF NOT (OLD.rol <=> NEW.rol) THEN
        UPDATE sesion SET fin = NOW(), motivo_cierre = 'CAMBIO_ROL'
         WHERE usuario_id = NEW.usuario_id AND fin IS NULL;
    END IF;
END$$

DELIMITER ;

-- ---------------------------------------------------------------- verificación
SELECT EVENT_OBJECT_TABLE AS tabla, TRIGGER_NAME, ACTION_TIMING, EVENT_MANIPULATION
  FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = 'logitrack' ORDER BY tabla, ACTION_TIMING, TRIGGER_NAME;
