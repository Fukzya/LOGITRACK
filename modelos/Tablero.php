<?php
/*
 * ============================================================================================
 * QUÉ ES:  el "modelo" de las tarjetas "Hoy" del menú (pendientes, viajes en curso y programados, clientes): el ÚNICO lugar con las consultas SQL de esa parte.
 *          La API (api/) lo llama; él arma el SELECT / INSERT / UPDATE y lo manda a la base
 *          con parámetros (:nombre), nunca pegando texto del usuario en el SQL.
 * PARA CAMBIAR:
 *   - Por qué columnas se puede ordenar el listado -> ORDENABLES ("CAMBIAR:" abajo).
 *   - Qué columnas trae el listado o cómo filtra   -> la función listar().
 *   - Las reglas (fechas, capacidad, licencias...) NO están acá: están en la base, en
 *     triggers y procedimientos (database/cambios/).
 * Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
 * ============================================================================================
 */
require_once __DIR__ . '/Modelo.php';

/** Números del menú principal. Todo sale de vistas: la definición de cada cosa vive en la base. */
class Tablero extends Modelo
{
    public function resumen(): array
    {
        return [
            'pendientes' => $this->fila(
                "SELECT COUNT(*) AS total,
                        COALESCE(SUM(fecha_envio >= DATE_FORMAT(CURDATE(), '%Y-%m-01')), 0) AS este_mes
                   FROM vista_envios_pendientes"),
            // Pendientes que todavía no tienen viaje (se cargaron con "Asignar más tarde").
            'sin_viaje' => $this->fila('SELECT COUNT(*) AS total FROM vista_envios_sin_viaje'),
            'en_curso' => $this->fila(
                'SELECT COUNT(*) AS total, COALESCE(SUM(horas_atraso > 0), 0) AS atrasados
                   FROM vista_viajes_en_curso'),
            // CAMBIAR: "próximos 7 días": cambiar el 7 de INTERVAL 7 DAY (y el texto de la tarjeta en index.php).
            'proximos' => $this->fila(
                'SELECT COUNT(*) AS total FROM vista_viajes_programados
                  WHERE fecha_salida < NOW() + INTERVAL 7 DAY'),
            // CAMBIAR: cuántos clientes muestra el ranking: el número de LIMIT 5.
            'top_clientes' => $this->pdo->query(
                'SELECT cliente_dni, nombre_completo, provincia_nombre, total_envios
                   FROM vista_clientes_con_envios
                  WHERE activo = 1
                  ORDER BY total_envios DESC, nombre_completo
                  LIMIT 5')->fetchAll(PDO::FETCH_ASSOC),
        ];
    }
}
