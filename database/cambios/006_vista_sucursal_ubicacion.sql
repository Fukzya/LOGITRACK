-- =============================================================================
-- Cambio 006: vista vista_sucursal_ubicacion
-- =============================================================================
-- Para qué: en todas las pantallas una sucursal se muestra con su provincia, por ejemplo
-- "Sucursal Puerto 46 (Corrientes)", porque el nombre solo no dice dónde queda.
--
-- La provincia no está en la tabla sucursal (sería una dependencia transitiva: sucursal ->
-- localidad -> provincia, lo que 3FN evita). Se obtiene con dos JOIN. En lugar de repetir esos JOIN
-- y el armado del texto en cada consulta, la vista los resuelve una sola vez y el resto del sistema
-- la usa como si fuera una tabla.
-- =============================================================================

USE logitrack;

CREATE OR REPLACE VIEW vista_sucursal_ubicacion AS
SELECT s.sucursal_cod,
       s.sucursal_nombre,
       CONCAT(s.sucursal_nombre, ' (', p.provincia_nombre, ')') AS sucursal_completa,
       s.direccion,
       l.localidad_cod,
       l.localidad_nombre,
       p.provincia_cod,
       p.provincia_nombre,
       s.activo
  FROM sucursal s
  JOIN localidad l ON l.localidad_cod = s.sucursal_localidad_cod
  JOIN provincia p ON p.provincia_cod = l.provincia_cod;

-- Verificación
SELECT sucursal_cod, sucursal_completa, localidad_nombre FROM vista_sucursal_ubicacion ORDER BY provincia_nombre LIMIT 10;
