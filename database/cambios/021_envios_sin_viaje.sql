-- =====================================================================================
-- 021 - Vista de envíos sin viaje (pendientes de asignación)
-- =====================================================================================
-- QUÉ:
--   * vista_envios_sin_viaje: los envíos pendientes (vista_envios_pendientes: activos y
--     sin estado final) que no tienen NINGUNA fila en viaje_envio. Son los que se crearon
--     con "Asignar más tarde" en el asistente, o a los que les quitaron el viaje.
--   * GRANT SELECT a rol_operador (rol_admin lo hereda). Como el 016 recrea los roles, el
--     mismo GRANT está en $permisosExtra de database/configurar_cuentas.php.
--
-- POR QUÉ: el asistente "Nuevo envío" deja crear un envío sin viaje, pero no había dónde
--   verlos después: "Solo pendientes" muestra los no entregados (623), no los que faltan
--   asignar (11 al 27/11/2026). La definición de "sin viaje" queda en UN lugar (esta
--   vista) y la usan el menú (tarjeta "Envíos pendientes"), el filtro "Sin viaje" de la
--   pantalla Envíos y el botón "Asignar viaje".
--   Los 802 envíos de 2024 sin viaje ya están entregados o devueltos (datos de prueba
--   viejos): no aparecen porque no están pendientes.
-- =====================================================================================

-- CAMBIAR: qué cuenta como "sin viaje" (hoy: pendiente y sin ninguna asignación).
CREATE OR REPLACE VIEW vista_envios_sin_viaje AS
SELECT p.*
  FROM vista_envios_pendientes p
 WHERE NOT EXISTS (SELECT 1 FROM viaje_envio ve WHERE ve.nro_envio = p.nro_envio);

GRANT SELECT ON logitrack.vista_envios_sin_viaje TO rol_operador;

-- Control: cuántos hay.
SELECT COUNT(*) AS envios_sin_viaje FROM vista_envios_sin_viaje;
