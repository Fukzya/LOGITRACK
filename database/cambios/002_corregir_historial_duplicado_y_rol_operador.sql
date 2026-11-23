-- 002 - Historial duplicado al crear envío + rol de operador1
--
-- 1) procedimiento_crear_envio insertaba "En sucursal origen" en historial_estado,
--    y trigger_after_insert_envio inserta lo mismo. Resultado: dos filas por envío.
--    Se deja solo el trigger (cubre cualquier INSERT en envio, no solo el procedimiento)
--    y se recrea el procedimiento sin ese INSERT.
-- 2) Se borran las filas duplicadas que ya quedaron cargadas (las que insertó el
--    procedimiento cuando el trigger ya había insertado la suya).
-- 3) operador1 tenía rol admin; pasa a operador.
--
-- Ejecutar UNA sola vez en HeidiSQL sobre la base logitrack, después del 001.

USE logitrack;

DROP PROCEDURE IF EXISTS procedimiento_crear_envio;

DELIMITER //
CREATE PROCEDURE `procedimiento_crear_envio`(
    IN fecha_del_envio        DATE,
    IN peso_del_envio         DECIMAL(8,2),
    IN codigo_tipo_contenido  CHAR(3),
    IN dni_remitente          INT,
    IN dni_destinatario       INT,
    IN codigo_sucursal_origen VARCHAR(10),
    IN codigo_sucursal_destino VARCHAR(10),
    IN id_direccion_entrega   INT
)
BEGIN
    -- El estado inicial "En sucursal origen" lo registra trigger_after_insert_envio.
    INSERT INTO envio (
        fecha_envio, peso_kg, tipo_cont_cod,
        remitente_dni, destinatario_dni,
        sucursal_orig_cod, sucursal_dest_cod,
        direccion_entrega_id
    ) VALUES (
        fecha_del_envio, peso_del_envio, codigo_tipo_contenido,
        dni_remitente, dni_destinatario,
        codigo_sucursal_origen, codigo_sucursal_destino,
        id_direccion_entrega
    );

    SELECT LAST_INSERT_ID() AS nro_envio_creado;
END//
DELIMITER ;

-- Borra la fila del procedimiento solo si el mismo envío ya tiene la del trigger.
DELETE p
FROM historial_estado p
JOIN historial_estado t
  ON  t.nro_envio    = p.nro_envio
  AND t.estado       = 'En sucursal origen'
  AND t.observacion  = 'Estado inicial registrado automáticamente por trigger'
WHERE p.estado      = 'En sucursal origen'
  AND p.observacion = 'Envío registrado automáticamente';

UPDATE usuario SET rol = 'operador' WHERE username = 'operador1';
