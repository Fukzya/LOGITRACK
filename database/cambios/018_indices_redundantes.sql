-- =====================================================================================
-- 018 - Índices: se borran dos índices redundantes
-- =====================================================================================
-- QUÉ:
--   * historial_estado.idx_historial_envio (nro_envio)
--   * viaje_envio.fk_ve_envio (nro_envio)
--
-- POR QUÉ: regla del PREFIJO IZQUIERDO. Un índice compuesto (a, b, c) sirve para buscar por
-- a, por (a, b) y por (a, b, c). Entonces:
--   * idx_historial_envio (nro_envio) ya lo cubre idx_historial_envio_fecha
--     (nro_envio, fecha_hora, id_historial), creado en el 015;
--   * fk_ve_envio (nro_envio) ya lo cubre el UNIQUE uq_viaje_envio_tramo (nro_envio, tramo).
-- Venían del volcado original. No daban resultados incorrectos, pero cada alta de un estado o de
-- una asignación actualizaba un índice de más (los índices aceleran lecturas y cuestan en las
-- escrituras). Las claves foráneas sobre nro_envio siguen funcionando: InnoDB usa el índice
-- compuesto, que empieza por esa columna.
--
-- Comprobar: EXPLAIN SELECT estado FROM historial_estado WHERE nro_envio = 2749
--            ORDER BY fecha_hora DESC, id_historial DESC LIMIT 1;   -> key = idx_historial_envio_fecha
-- Se puede volver a correr (IF EXISTS).
-- =====================================================================================

DROP INDEX IF EXISTS idx_historial_envio ON historial_estado;
DROP INDEX IF EXISTS fk_ve_envio ON viaje_envio;
