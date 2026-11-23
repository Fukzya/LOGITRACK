-- 003 - Tablas catálogo: estado_envio, tipo_incidente, tipo_licencia
--
-- historial_estado.estado, incidente.tipo_incidente y chofer.chofer_tipo_licencia eran
-- texto libre. Con eso aparecieron valores inconsistentes para el mismo estado
-- ('RECIBIDO' / 'En sucursal origen', 'EN_TRANSITO' / 'En tránsito', 'Redirigido').
-- Ahora cada uno es una FK a su tabla catálogo, y el catálogo guarda los datos que
-- dependen del código (nombre para mostrar, si el estado es final, descripción).
--
-- La PK de cada catálogo es el mismo código que ya se usaba, así las columnas
-- existentes pasan a ser FK sin cambiar de tipo.
--
-- Ejecutar UNA sola vez en HeidiSQL sobre la base logitrack, después del 002.

USE logitrack;

-- ---------------------------------------------------------------------------
-- 1. Catálogos
-- ---------------------------------------------------------------------------

CREATE TABLE estado_envio (
    estado_cod    VARCHAR(30) NOT NULL,
    estado_nombre VARCHAR(50) NOT NULL,
    es_final      TINYINT(1)  NOT NULL DEFAULT 0,  -- 1 = el envío ya no se mueve más
    PRIMARY KEY (estado_cod),
    UNIQUE KEY uq_estado_nombre (estado_nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO estado_envio (estado_cod, estado_nombre, es_final) VALUES
    ('RECIBIDO',    'Recibido en sucursal origen', 0),
    ('EN_TRANSITO', 'En tránsito',                 0),
    ('EN_SUCURSAL', 'En sucursal',                 0),
    ('DEMORADO',    'Demorado',                    0),
    ('REDIRIGIDO',  'Redirigido',                  0),
    ('ENTREGADO',   'Entregado',                   1),
    ('DEVUELTO',    'Devuelto',                    1);

CREATE TABLE tipo_incidente (
    tipo_incidente_cod    VARCHAR(50) NOT NULL,
    tipo_incidente_nombre VARCHAR(80) NOT NULL,
    PRIMARY KEY (tipo_incidente_cod),
    UNIQUE KEY uq_tipo_incidente_nombre (tipo_incidente_nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO tipo_incidente (tipo_incidente_cod, tipo_incidente_nombre) VALUES
    ('DEMORA',    'Demora'),
    ('AVERIA',    'Avería'),
    ('ACCIDENTE', 'Accidente'),
    ('ROBO',      'Robo'),
    ('OTRO',      'Otro');

CREATE TABLE tipo_licencia (
    licencia_cod         VARCHAR(10)  NOT NULL,
    licencia_descripcion VARCHAR(120) NOT NULL,
    PRIMARY KEY (licencia_cod)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO tipo_licencia (licencia_cod, licencia_descripcion) VALUES
    ('B', 'Automóviles y camionetas'),
    ('C', 'Camiones sin acoplado'),
    ('D', 'Transporte de pasajeros'),
    ('E', 'Camiones articulados o con acoplado');

-- ---------------------------------------------------------------------------
-- 2. Unificar los estados que estaban escritos distinto
-- ---------------------------------------------------------------------------

UPDATE historial_estado SET estado = 'RECIBIDO'    WHERE estado = 'En sucursal origen';
UPDATE historial_estado SET estado = 'EN_TRANSITO' WHERE estado = 'En tránsito';
UPDATE historial_estado SET estado = 'REDIRIGIDO'  WHERE estado = 'Redirigido';

-- ---------------------------------------------------------------------------
-- 3. Claves foráneas (las columnas ya tienen el mismo tipo que las PK)
-- ---------------------------------------------------------------------------

ALTER TABLE historial_estado
    ADD CONSTRAINT fk_hist_estado FOREIGN KEY (estado)
        REFERENCES estado_envio (estado_cod) ON UPDATE CASCADE;

ALTER TABLE incidente
    ADD CONSTRAINT fk_incidente_tipo FOREIGN KEY (tipo_incidente)
        REFERENCES tipo_incidente (tipo_incidente_cod) ON UPDATE CASCADE;

ALTER TABLE chofer
    ADD CONSTRAINT fk_chofer_licencia FOREIGN KEY (chofer_tipo_licencia)
        REFERENCES tipo_licencia (licencia_cod) ON UPDATE CASCADE;

-- ---------------------------------------------------------------------------
-- 4. Triggers que escribían estados con texto libre
-- ---------------------------------------------------------------------------

DROP TRIGGER IF EXISTS trigger_after_insert_envio;
DROP TRIGGER IF EXISTS trigger_after_update_envio;

DELIMITER //
CREATE TRIGGER trigger_after_insert_envio
AFTER INSERT ON envio
FOR EACH ROW
BEGIN
    INSERT INTO historial_estado (
        nro_envio, sucursal_cod, estado, fecha_hora, observacion
    ) VALUES (
        NEW.nro_envio,
        NEW.sucursal_orig_cod,
        'RECIBIDO',
        NOW(),
        'Estado inicial registrado automáticamente por trigger'
    );
END//

CREATE TRIGGER trigger_after_update_envio
AFTER UPDATE ON envio
FOR EACH ROW
BEGIN
    IF NEW.sucursal_dest_cod <> OLD.sucursal_dest_cod THEN
        INSERT INTO historial_estado (
            nro_envio, sucursal_cod, estado, fecha_hora, observacion
        ) VALUES (
            NEW.nro_envio,
            NEW.sucursal_dest_cod,
            'REDIRIGIDO',
            NOW(),
            CONCAT('Destino cambiado de sucursal ', OLD.sucursal_dest_cod, ' a ', NEW.sucursal_dest_cod)
        );
    END IF;
END//
DELIMITER ;

-- ---------------------------------------------------------------------------
-- 5. Vistas que comparaban contra texto fijo
-- ---------------------------------------------------------------------------

-- Antes: WHERE estado <> 'Entregado' (los DEVUELTO aparecían como pendientes).
-- Ahora: pendiente = el último estado no es final, según el catálogo.
CREATE OR REPLACE VIEW vista_envios_pendientes AS
SELECT e.nro_envio,
       e.fecha_envio,
       e.peso_kg,
       tc.tipo_cont_nombre                  AS tipo_contenido,
       CONCAT(rem.nombre, ' ', rem.apellido)   AS nombre_remitente,
       CONCAT(dest.nombre, ' ', dest.apellido) AS nombre_destinatario,
       so.sucursal_nombre                   AS sucursal_origen,
       sd.sucursal_nombre                   AS sucursal_destino,
       est.estado_nombre                    AS ultimo_estado,
       h_ultimo.fecha_hora                  AS fecha_ultimo_estado
FROM envio e
JOIN tipo_contenido tc ON e.tipo_cont_cod     = tc.tipo_cont_cod
JOIN cliente rem       ON e.remitente_dni     = rem.cliente_dni
JOIN cliente dest      ON e.destinatario_dni  = dest.cliente_dni
JOIN sucursal so       ON e.sucursal_orig_cod = so.sucursal_cod
JOIN sucursal sd       ON e.sucursal_dest_cod = sd.sucursal_cod
JOIN historial_estado h_ultimo
     ON h_ultimo.id_historial = (SELECT MAX(h.id_historial)
                                 FROM historial_estado h
                                 WHERE h.nro_envio = e.nro_envio)
JOIN estado_envio est  ON h_ultimo.estado     = est.estado_cod
WHERE est.es_final = 0;

-- Se agrega el nombre del estado para mostrar.
CREATE OR REPLACE VIEW vista_historial_completo AS
SELECT h.id_historial,
       h.nro_envio,
       h.estado,
       est.estado_nombre,
       h.fecha_hora,
       DATE_FORMAT(h.fecha_hora, '%d/%m/%Y %H:%i') AS fecha_formateada,
       s.sucursal_nombre,
       h.sucursal_cod,
       h.observacion,
       CONCAT(rem.nombre, ' ', rem.apellido)   AS remitente,
       CONCAT(dest.nombre, ' ', dest.apellido) AS destinatario
FROM historial_estado h
JOIN estado_envio est ON h.estado         = est.estado_cod
JOIN sucursal s       ON h.sucursal_cod   = s.sucursal_cod
JOIN envio e          ON h.nro_envio      = e.nro_envio
JOIN cliente rem      ON e.remitente_dni  = rem.cliente_dni
JOIN cliente dest     ON e.destinatario_dni = dest.cliente_dni;
