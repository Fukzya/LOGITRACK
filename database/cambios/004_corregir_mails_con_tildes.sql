-- 004 - Calidad de datos (validez): mails de clientes con tildes
--
-- 1773 de 5001 clientes tenían mails con letras acentuadas o ñ (ej. 'maría.castillo51@gmail.com'),
-- que no son direcciones válidas: el formulario y la API (validación de formato) los rechazan,
-- así que esos clientes no se podían editar. Los datos de prueba copiaron las tildes del nombre.
-- Se reemplaza cada letra por su versión sin tilde.
--
-- Ejecutar UNA sola vez en HeidiSQL sobre la base logitrack, después del 003.

USE logitrack;

UPDATE cliente
SET mail = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
           REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(mail,
           'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ü', 'u'), 'ñ', 'n'),
           'Á', 'A'), 'É', 'E'), 'Í', 'I'), 'Ó', 'O'), 'Ú', 'U'), 'Ü', 'U'), 'Ñ', 'N')
WHERE mail REGEXP '[^ -~]';

-- Control: tiene que dar 0.
SELECT COUNT(*) AS mails_con_caracteres_no_ascii FROM cliente WHERE mail REGEXP '[^ -~]';
