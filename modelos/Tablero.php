<?php
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
            'en_curso' => $this->fila(
                'SELECT COUNT(*) AS total, COALESCE(SUM(horas_atraso > 0), 0) AS atrasados
                   FROM vista_viajes_en_curso'),
            'proximos' => $this->fila(
                'SELECT COUNT(*) AS total FROM vista_viajes_programados
                  WHERE fecha_salida < NOW() + INTERVAL 7 DAY'),
            'top_clientes' => $this->pdo->query(
                'SELECT cliente_dni, nombre_completo, provincia_nombre, total_envios
                   FROM vista_clientes_con_envios
                  WHERE activo = 1
                  ORDER BY total_envios DESC, nombre_completo
                  LIMIT 5')->fetchAll(PDO::FETCH_ASSOC),
        ];
    }
}
