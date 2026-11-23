<?php
require_once __DIR__ . '/Modelo.php';

/** Modelo Incidente: problemas ocurridos durante un viaje. */
class Incidente extends Modelo
{
    private const ORDENABLES = [
        'fecha_hora'     => 'i.fecha_hora',
        'incidente_id'   => 'i.incidente_id',
        'viaje_id'       => 'i.viaje_id',
        'tipo_incidente' => 't.tipo_incidente_nombre',
    ];

    /** $f: viaje_id, tipo, fecha_desde, fecha_hasta (opcionales). */
    public function listar(array $f, array $p): array
    {
        $cond = [];
        $par  = [];
        if (!empty($f['viaje_id']))    { $cond[] = 'i.viaje_id = :v';         $par[':v'] = $f['viaje_id']; }
        if (!empty($f['tipo']))        { $cond[] = 'i.tipo_incidente = :t';   $par[':t'] = $f['tipo']; }
        if (!empty($f['fecha_desde'])) { $cond[] = 'i.fecha_hora >= :fd';     $par[':fd'] = $f['fecha_desde'] . ' 00:00:00'; }
        if (!empty($f['fecha_hasta'])) { $cond[] = 'i.fecha_hora <= :fh';     $par[':fh'] = $f['fecha_hasta'] . ' 23:59:59'; }

        return $this->paginar(
            'i.incidente_id, i.viaje_id, i.fecha_hora, i.tipo_incidente, t.tipo_incidente_nombre, i.descripcion',
            'incidente i JOIN tipo_incidente t ON t.tipo_incidente_cod = i.tipo_incidente',
            $cond, $par, self::ORDENABLES, $p['orden'], $p['dir'], $p['pagina']
        );
    }

    public function crear(array $i): int
    {
        $this->ejecutar('INSERT INTO incidente (viaje_id, fecha_hora, tipo_incidente, descripcion) VALUES (:v, :f, :t, :d)',
                        [':v' => $i['viaje_id'], ':f' => $i['fecha_hora'], ':t' => $i['tipo_incidente'], ':d' => $i['descripcion']]);
        return (int)$this->pdo->lastInsertId();
    }

    public function actualizar(int $id, array $i): bool
    {
        return $this->ejecutar('UPDATE incidente SET viaje_id = :v, fecha_hora = :f, tipo_incidente = :t, descripcion = :d
                                WHERE incidente_id = :id',
                               [':v' => $i['viaje_id'], ':f' => $i['fecha_hora'], ':t' => $i['tipo_incidente'],
                                ':d' => $i['descripcion'], ':id' => $id]) > 0;
    }

    public function eliminar(int $id): bool
    {
        return $this->ejecutar('DELETE FROM incidente WHERE incidente_id = :id', [':id' => $id]) > 0;
    }
}
