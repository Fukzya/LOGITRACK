-- 011 - Sesiones de la aplicación registradas en la base
--
-- Qué: tabla SESION (una fila por login), dos funciones, tres procedimientos, un trigger en
-- USUARIO y una vista con las sesiones abiertas.
--
-- Por qué:
--  * Antes la sesión vivía solo en PHP: la base no sabía quién estaba conectado ni cómo salió.
--  * Si el admin daba de baja a un usuario (o le cambiaba el rol), la sesión que tenía abierta
--    seguía funcionando hasta que se deslogueara solo.
--  * El vencimiento (30 min sin uso) se controlaba solo en la API y no en las pantallas.
--  Ahora la base decide si una sesión sigue activa (funcion_sesion_activa) y PHP la consulta
--  en cada pedido. Se permiten varias sesiones abiertas por usuario (decisión del usuario):
--  por ejemplo la PC de la sucursal y el celular.
--
-- Del id de sesión de PHP se guarda solo el hash SHA-256 (token_hash): quien lea la tabla no
-- puede usarlo para hacerse pasar por el usuario.
--
-- Se corre una sola vez, después del 010.

USE logitrack;

CREATE TABLE sesion (
    sesion_id      INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id     INT          NOT NULL,
    token_hash     CHAR(64)     NOT NULL,
    inicio         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fin            DATETIME     NULL,
    motivo_cierre  ENUM('LOGOUT','VENCIDA','CERRADA_POR_ADMIN','BAJA_USUARIO','CAMBIO_ROL') NULL,
    ip             VARCHAR(45)  NULL,     -- 45 caracteres: entra una IPv6
    navegador      VARCHAR(255) NULL,
    CONSTRAINT uq_sesion_token UNIQUE (token_hash),
    CONSTRAINT fk_sesion_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (usuario_id),
    -- Una sesión cerrada siempre tiene motivo, y una abierta no tiene ninguno de los dos.
    CONSTRAINT chk_sesion_cierre CHECK ((fin IS NULL AND motivo_cierre IS NULL)
                                     OR (fin IS NOT NULL AND motivo_cierre IS NOT NULL)),
    -- "Sesiones abiertas de un usuario" (el trigger de USUARIO y la pantalla Sesiones).
    INDEX idx_sesion_usuario_fin (usuario_id, fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

DELIMITER //

-- Minutos sin uso después de los cuales una sesión vence. Único lugar donde está el número
-- (PHP usa el mismo valor para session.gc_maxlifetime).
CREATE FUNCTION funcion_minutos_sesion() RETURNS INT
DETERMINISTIC NO SQL
BEGIN
    RETURN 30;
END //

-- 1 si la sesión está abierta y se usó en los últimos funcion_minutos_sesion() minutos.
CREATE FUNCTION funcion_sesion_activa(p_sesion_id INT) RETURNS TINYINT(1)
NOT DETERMINISTIC READS SQL DATA
BEGIN
    DECLARE v_activa TINYINT(1) DEFAULT 0;
    SELECT (fin IS NULL AND ultimo_acceso >= NOW() - INTERVAL funcion_minutos_sesion() MINUTE)
      INTO v_activa
      FROM sesion WHERE sesion_id = p_sesion_id;
    RETURN COALESCE(v_activa, 0);
END //

-- Cierra las sesiones abiertas que pasaron el tiempo sin uso. La fecha de fin es el momento en
-- que vencieron (último acceso + 30 min), no el momento en que se detectó.
CREATE PROCEDURE procedimiento_cerrar_sesiones_vencidas()
BEGIN
    UPDATE sesion
       SET fin = ultimo_acceso + INTERVAL funcion_minutos_sesion() MINUTE,
           motivo_cierre = 'VENCIDA'
     WHERE fin IS NULL
       AND ultimo_acceso < NOW() - INTERVAL funcion_minutos_sesion() MINUTE;
END //

-- Registra un login. Rechaza usuarios inexistentes o dados de baja.
CREATE PROCEDURE procedimiento_iniciar_sesion(
    IN  p_usuario_id INT,
    IN  p_token_hash CHAR(64),
    IN  p_ip         VARCHAR(45),
    IN  p_navegador  VARCHAR(255),
    OUT p_sesion_id  INT
)
BEGIN
    IF NOT EXISTS (SELECT 1 FROM usuario WHERE usuario_id = p_usuario_id AND activo = 1) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El usuario no existe o está dado de baja.';
    END IF;

    CALL procedimiento_cerrar_sesiones_vencidas();

    INSERT INTO sesion (usuario_id, token_hash, ip, navegador)
    VALUES (p_usuario_id, p_token_hash, p_ip, LEFT(p_navegador, 255));
    SET p_sesion_id = LAST_INSERT_ID();
END //

-- Cierra una sesión abierta con el motivo indicado (LOGOUT, CERRADA_POR_ADMIN...).
CREATE PROCEDURE procedimiento_cerrar_sesion(IN p_sesion_id INT, IN p_motivo VARCHAR(20))
BEGIN
    UPDATE sesion SET fin = NOW(), motivo_cierre = p_motivo
     WHERE sesion_id = p_sesion_id AND fin IS NULL;
    IF ROW_COUNT() = 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La sesión no existe o ya estaba cerrada.';
    END IF;
END //

-- Si un usuario se da de baja o cambia de rol, se cierran todas sus sesiones abiertas.
-- (Con el rol viejo guardado en la sesión podría seguir entrando a pantallas de admin.)
CREATE TRIGGER trigger_after_update_usuario
AFTER UPDATE ON usuario
FOR EACH ROW
BEGIN
    IF OLD.activo = 1 AND NEW.activo = 0 THEN
        UPDATE sesion SET fin = NOW(), motivo_cierre = 'BAJA_USUARIO'
         WHERE usuario_id = NEW.usuario_id AND fin IS NULL;
    ELSEIF NOT (OLD.rol <=> NEW.rol) THEN
        UPDATE sesion SET fin = NOW(), motivo_cierre = 'CAMBIO_ROL'
         WHERE usuario_id = NEW.usuario_id AND fin IS NULL;
    END IF;
END //

DELIMITER ;

-- Sesiones abiertas y todavía activas, con su usuario y cuánto hace que se usaron.
CREATE VIEW vista_sesiones_abiertas AS
SELECT s.sesion_id,
       s.usuario_id,
       u.username,
       u.rol,
       s.inicio,
       s.ultimo_acceso,
       TIMESTAMPDIFF(MINUTE, s.inicio, NOW())        AS minutos_conectado,
       TIMESTAMPDIFF(MINUTE, s.ultimo_acceso, NOW()) AS minutos_sin_uso,
       s.ip,
       s.navegador,
       s.token_hash
  FROM sesion s
  JOIN usuario u ON u.usuario_id = s.usuario_id
 WHERE s.fin IS NULL
   AND s.ultimo_acceso >= NOW() - INTERVAL funcion_minutos_sesion() MINUTE;
