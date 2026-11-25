-- Prueba de volumen: 100.000 clientes y 100.000 envíos en una COPIA de la base.
--
-- NO se corre sobre logitrack. Antes de usarlo:
--   mysql -uroot -e "CREATE DATABASE logitrack_carga CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci"
--   mysqldump -uroot --routines --triggers logitrack | mysql -uroot logitrack_carga
--   Get-Content -Raw -Encoding UTF8 carga_100k.sql | mysql -uroot --default-character-set=utf8mb4
--
-- Carga datos válidos (los triggers están activos y controlan cada fila):
--   100.000 clientes con una dirección cada uno;
--   100.000 envíos (fecha entre 01/01/2025 y hoy, la dirección es del destinatario); el trigger
--   de envío registra el RECIBIDO de cada uno;
--   60.000 pasan a En tránsito y 30.000 de esos a Entregado.
-- Va en lotes de 1.000 filas por transacción: con un COMMIT por fila, cada fila espera la
-- escritura a disco. Al final muestra cuánto tardó cada parte.

USE logitrack_carga;

DROP TABLE IF EXISTS zz_tiempos;
CREATE TABLE zz_tiempos (paso VARCHAR(60), segundos DECIMAL(8,1));

DELIMITER $$
DROP PROCEDURE IF EXISTS zz_cargar$$
CREATE PROCEDURE zz_cargar(IN p_cantidad INT, IN p_lote INT)
BEGIN
    DECLARE v_desde INT DEFAULT 0;
    DECLARE v_inicio DATETIME(3);
    DECLARE v_primera_dir INT;

    IF DATABASE() <> 'logitrack_carga' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Este script es solo para la copia logitrack_carga.';
    END IF;

    -- Localidades y sucursales numeradas, para repartir los datos con el resto de una división.
    DROP TEMPORARY TABLE IF EXISTS zz_loc;
    CREATE TEMPORARY TABLE zz_loc (n INT PRIMARY KEY, localidad_cod INT)
        SELECT ROW_NUMBER() OVER (ORDER BY localidad_cod) - 1 AS n, localidad_cod FROM localidad;
    DROP TEMPORARY TABLE IF EXISTS zz_suc;
    CREATE TEMPORARY TABLE zz_suc (n INT PRIMARY KEY, sucursal_cod VARCHAR(10))
        SELECT ROW_NUMBER() OVER (ORDER BY sucursal_cod) - 1 AS n, sucursal_cod FROM sucursal WHERE activo = 1;
    SET @n_loc = (SELECT COUNT(*) FROM zz_loc), @n_suc = (SELECT COUNT(*) FROM zz_suc);

    -- 1. Clientes
    SET v_inicio = NOW(3), v_desde = 0;
    WHILE v_desde < p_cantidad DO
        START TRANSACTION;
        INSERT INTO cliente (cliente_dni, nombre, apellido, mail, telefono, localidad_cod)
        SELECT 60000000 + s.seq,
               ELT(1 + s.seq % 10, 'Ana', 'Juan', 'María', 'Pedro', 'Lucía', 'Diego', 'Sofía', 'Martín', 'Laura', 'Pablo'),
               ELT(1 + (s.seq DIV 10) % 10, 'Gómez', 'Pérez', 'Rodríguez', 'Fernández', 'López', 'Díaz', 'Martínez', 'Romero', 'Sosa', 'Torres'),
               CONCAT('carga', s.seq, '@prueba.com'), CONCAT('11', LPAD(s.seq, 8, '0')),
               l.localidad_cod
          FROM seq_1_to_1000000 s JOIN zz_loc l ON l.n = s.seq % @n_loc
         WHERE s.seq > v_desde AND s.seq <= LEAST(v_desde + p_lote, p_cantidad);
        COMMIT;
        SET v_desde = v_desde + p_lote;
    END WHILE;
    INSERT INTO zz_tiempos VALUES ('clientes', TIMESTAMPDIFF(MICROSECOND, v_inicio, NOW(3)) / 1e6);

    -- 2. Una dirección por cliente
    SET v_inicio = NOW(3), v_desde = 0;
    SET v_primera_dir = (SELECT AUTO_INCREMENT FROM information_schema.TABLES
                          WHERE TABLE_SCHEMA = 'logitrack_carga' AND TABLE_NAME = 'direccion');
    WHILE v_desde < p_cantidad DO
        START TRANSACTION;
        INSERT INTO direccion (calle_y_numero, localidad_cod, cliente_dni)
        SELECT CONCAT('Calle ', s.seq % 500, ' ', 100 + s.seq % 4000), c.localidad_cod, c.cliente_dni
          FROM seq_1_to_1000000 s JOIN cliente c ON c.cliente_dni = 60000000 + s.seq
         WHERE s.seq > v_desde AND s.seq <= LEAST(v_desde + p_lote, p_cantidad)
         ORDER BY s.seq;
        COMMIT;
        SET v_desde = v_desde + p_lote;
    END WHILE;
    INSERT INTO zz_tiempos VALUES ('direcciones', TIMESTAMPDIFF(MICROSECOND, v_inicio, NOW(3)) / 1e6);

    -- 3. Envíos (triggers: valida la dirección y registra el RECIBIDO)
    SET v_inicio = NOW(3), v_desde = 0;
    WHILE v_desde < p_cantidad DO
        START TRANSACTION;
        INSERT INTO envio (fecha_envio, peso_kg, tipo_cont_cod, remitente_dni, destinatario_dni,
                           sucursal_orig_cod, sucursal_dest_cod, direccion_entrega_id)
        SELECT LEAST(DATE '2025-01-01' + INTERVAL (s.seq % 690) DAY, CURDATE()),
               1 + (s.seq % 500) / 10,
               ELT(1 + s.seq % 3, 'STD', 'FRG', 'PEL'),
               60000000 + s.seq,
               d.cliente_dni,
               so.sucursal_cod,
               sd.sucursal_cod,
               d.direccion_id
          FROM seq_1_to_1000000 s
          JOIN direccion d ON d.direccion_id = v_primera_dir + (s.seq * 7919) % p_cantidad
          JOIN zz_suc so ON so.n = s.seq % @n_suc
          JOIN zz_suc sd ON sd.n = (s.seq % @n_suc + 1 + s.seq % (@n_suc - 1)) % @n_suc
         WHERE s.seq > v_desde AND s.seq <= LEAST(v_desde + p_lote, p_cantidad);
        COMMIT;
        SET v_desde = v_desde + p_lote;
    END WHILE;
    INSERT INTO zz_tiempos VALUES ('envios (+ RECIBIDO por trigger)', TIMESTAMPDIFF(MICROSECOND, v_inicio, NOW(3)) / 1e6);

    -- 4. Más estados: 60% En tránsito, la mitad de esos Entregado
    SET v_inicio = NOW(3);
    SET @primer_envio = (SELECT MIN(nro_envio) FROM envio WHERE remitente_dni > 60000000);
    SET v_desde = 0;
    WHILE v_desde < p_cantidad DO
        START TRANSACTION;
        INSERT INTO historial_estado (nro_envio, sucursal_cod, estado, fecha_hora, observacion)
        SELECT e.nro_envio, e.sucursal_orig_cod, 'EN_TRANSITO', NOW(), 'Carga de prueba'
          FROM envio e
         WHERE e.nro_envio >= @primer_envio + v_desde AND e.nro_envio < @primer_envio + v_desde + p_lote
           AND e.nro_envio % 10 < 6;
        INSERT INTO historial_estado (nro_envio, sucursal_cod, estado, fecha_hora, observacion)
        SELECT e.nro_envio, e.sucursal_dest_cod, 'ENTREGADO', NOW(), 'Carga de prueba'
          FROM envio e
         WHERE e.nro_envio >= @primer_envio + v_desde AND e.nro_envio < @primer_envio + v_desde + p_lote
           AND e.nro_envio % 10 < 3;
        COMMIT;
        SET v_desde = v_desde + p_lote;
    END WHILE;
    INSERT INTO zz_tiempos VALUES ('estados (En tránsito y Entregado)', TIMESTAMPDIFF(MICROSECOND, v_inicio, NOW(3)) / 1e6);
END$$
DELIMITER ;

CALL zz_cargar(100000, 1000);
DROP PROCEDURE zz_cargar;

SELECT * FROM zz_tiempos;
SELECT (SELECT COUNT(*) FROM cliente) AS clientes, (SELECT COUNT(*) FROM direccion) AS direcciones,
       (SELECT COUNT(*) FROM envio) AS envios, (SELECT COUNT(*) FROM historial_estado) AS historial;
