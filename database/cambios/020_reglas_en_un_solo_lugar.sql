-- =====================================================================================
-- 020 - Cada regla del negocio vive en UN solo lugar: la base
-- =====================================================================================
-- QUÉ:
--   * funcion_licencia_maneja_especial(licencia): 1 si esa licencia puede manejar un
--     vehículo que pide licencia especial (camión, semirremolque). Es el ÚNICO lugar
--     donde está escrito "C o E". La usan:
--       - procedimiento_validar_recursos_viaje (lo llaman los triggers de viaje), y
--       - la pantalla "Nuevo envío", que la pide a la base para ofrecer solo los
--         choferes habilitados (antes el PHP y el JS tenían ['C', 'E'] escrito).
--   * trigger_before_insert_viaje_envio: el rechazo por capacidad ahora dice cuántos kg
--     quedan libres y cuánto pesa el envío (antes ese detalle lo armaba el PHP del
--     asistente, que repetía el control). El resto del trigger no cambia (017).
--   * Permisos: rol_operador ya no ejecuta funcion_carga_viaje_bloqueando (la usaba el
--     PHP, ahora solo los triggers, que corren con los permisos de su DEFINER) y sí
--     ejecuta la función nueva.
--
-- POR QUÉ: había reglas escritas dos veces, en el PHP y en triggers/procedimientos
--   (licencia C/E, vehículo y chofer activos y libres, viaje que ya salió, capacidad,
--   dirección del destinatario, llegada posterior a la salida, origen distinto del
--   destino). Si se cambia una sola copia, quedan en desacuerdo. Criterio acordado: una
--   regla funciona por la base O por el código, nunca por los dos. Las reglas quedan en
--   la base; el PHP solo valida el formato de los datos (obligatorio, número, largo),
--   abre/cierra la transacción y muestra el mensaje que devuelve la base.
-- =====================================================================================

DELIMITER $$

DROP FUNCTION IF EXISTS funcion_licencia_maneja_especial $$
CREATE FUNCTION funcion_licencia_maneja_especial(p_licencia VARCHAR(5))
RETURNS TINYINT
DETERMINISTIC
COMMENT '1 si la licencia habilita a manejar vehículos con licencia especial (camión, semirremolque).'
BEGIN
    -- CAMBIAR: licencias que pueden manejar camión y semirremolque (único lugar).
    RETURN p_licencia IN ('C', 'E');
END $$

DROP PROCEDURE IF EXISTS procedimiento_validar_recursos_viaje $$
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
    -- 020: la lista de licencias está en funcion_licencia_maneja_especial
    IF v_especial = 1 AND funcion_licencia_maneja_especial(v_licencia) = 0 THEN
        SET v_mensaje = CONCAT('Ese vehículo pide licencia especial y el chofer tiene licencia ', v_licencia, '.');
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
    DECLARE v_carga DECIMAL(10,2);
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

    -- 017: capacidad con la carga leída CON bloqueo (no la "foto" de la transacción).
    -- 020: el mensaje dice cuánto queda libre y cuánto pesa el envío.
    SET v_carga = funcion_carga_viaje_bloqueando(NEW.viaje_id);
    IF v_carga + v_peso > v_capacidad THEN
        SET v_mensaje = CONCAT('El viaje ', NEW.viaje_id, ' no tiene lugar: quedan ',
                               FORMAT(GREATEST(v_capacidad - v_carga, 0), 2, 'es_AR'),
                               ' kg libres y el envío pesa ', FORMAT(v_peso, 2, 'es_AR'), ' kg.');
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_mensaje;
    END IF;
END $$

DELIMITER ;

-- Permisos de la cuenta de la app (016). También en $permisosExtra de configurar_cuentas.php.
-- El GRANT va primero: el DROP FUNCTION de arriba le borra los permisos a la función, así que
-- si se vuelve a correr este script hay que volver a darlo. En esa segunda vez el REVOKE da
-- error 1403 (ese permiso ya no existe): es normal, no queda nada a medias.
GRANT EXECUTE ON FUNCTION logitrack.funcion_licencia_maneja_especial TO rol_operador;
REVOKE EXECUTE ON FUNCTION logitrack.funcion_carga_viaje_bloqueando FROM rol_operador;
