<?php
require_once __DIR__ . '/Modelo.php';

/** Modelo Viaje (baja física: el trigger trigger_before_delete_viaje impide borrar uno con envíos). */
class Viaje extends Modelo
{
    private const ORDENABLES = [
        'viaje_id'      => 'v.viaje_id',
        'fecha_salida'  => 'v.fecha_salida',
        'fecha_llegada' => 'v.fecha_llegada',
    ];

    /** $f: fecha_desde, fecha_hasta, chofer, solo_activos (en curso: sin fecha de llegada). */
    public function listar(array $f, array $p): array
    {
        $cond = [];
        $par  = [];
        if (!empty($f['fecha_desde'])) { $cond[] = 'v.fecha_salida >= :fd';     $par[':fd'] = $f['fecha_desde'] . ' 00:00:00'; }
        if (!empty($f['fecha_hasta'])) { $cond[] = 'v.fecha_salida <= :fh';     $par[':fh'] = $f['fecha_hasta'] . ' 23:59:59'; }
        if (!empty($f['chofer']))      { $cond[] = 'v.chofer_legajo = :chofer'; $par[':chofer'] = $f['chofer']; }
        if (!empty($f['solo_activos'])) { $cond[] = 'v.fecha_llegada IS NULL'; }

        return $this->paginar(
            "v.viaje_id, v.fecha_salida, v.fecha_llegada,
             v.vehiculo_patente, CONCAT(ve.marca, ' ', ve.modelo) AS vehiculo,
             v.chofer_legajo, CONCAT(c.nombre, ' ', c.apellido) AS chofer_nombre,
             (SELECT COUNT(*) FROM viaje_envio x WHERE x.viaje_id = v.viaje_id) AS cantidad_envios",
            'viaje v JOIN vehiculo ve ON ve.vehiculo_patente = v.vehiculo_patente
                     JOIN chofer c    ON c.chofer_legajo     = v.chofer_legajo',
            $cond, $par, self::ORDENABLES, $p['orden'], $p['dir'], $p['pagina']
        );
    }

    public function crear(array $v): int
    {
        $this->ejecutar('INSERT INTO viaje (fecha_salida, fecha_llegada, vehiculo_patente, chofer_legajo)
                         VALUES (:s, :l, :p, :c)',
                        [':s' => $v['fecha_salida'], ':l' => $v['fecha_llegada'],
                         ':p' => $v['vehiculo_patente'], ':c' => $v['chofer_legajo']]);
        return (int)$this->pdo->lastInsertId();
    }

    /** trigger_before_update_viaje rechaza una llegada anterior a la salida. */
    public function actualizar(int $id, array $v): bool
    {
        return $this->ejecutar('UPDATE viaje SET fecha_salida = :s, fecha_llegada = :l,
                                       vehiculo_patente = :p, chofer_legajo = :c
                                WHERE viaje_id = :id',
                               [':s' => $v['fecha_salida'], ':l' => $v['fecha_llegada'],
                                ':p' => $v['vehiculo_patente'], ':c' => $v['chofer_legajo'], ':id' => $id]) > 0;
    }

    /** Baja física. La impiden el trigger (si tiene envíos) o la FK de incidente (si tiene incidentes). */
    public function eliminar(int $id): bool
    {
        return $this->ejecutar('DELETE FROM viaje WHERE viaje_id = :id', [':id' => $id]) > 0;
    }
}
