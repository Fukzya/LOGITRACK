-- 001 - Baja lógica (columna activo)
-- Agrega la columna `activo` a las tablas que el código filtra por activo = 1.
-- `envio` no está acá porque database/logitrack.sql ya la incluye.
-- Ejecutar UNA sola vez en HeidiSQL sobre la base logitrack, después de importar logitrack.sql.

USE logitrack;

ALTER TABLE chofer    ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE cliente   ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE direccion ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE sucursal  ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE vehiculo  ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1;
