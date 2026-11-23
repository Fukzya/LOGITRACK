<?php
require_once __DIR__ . '/Modelo.php';

/** Modelo HistorialEstado: los estados por los que pasa cada envío. */
class HistorialEstado extends Modelo
{
    private const ORDENABLES = [
        'id_historial' => 'h.id_historial',
        'nro_envio'    => 'h.nro_envio, h.fecha_hora',
        'fecha_hora'   => 'h.fecha_hora',
    ];

    /** $f: nro_envio, estado, sucursal, fecha_desde, fecha_hasta (opcionales). */
    public function listar(array $f, array $p): array
    {
        $cond = [];
        $par  = [];
        if (!empty($f['nro_envio']))   { $cond[] = 'h.nro_envio = :n';     $par[':n'] = $f['nro_envio']; }
        if (!empty($f['estado']))      { $cond[] = 'h.estado = :estado';   $par[':estado'] = $f['estado']; }
        if (!empty($f['sucursal']))    { $cond[] = 'h.sucursal_cod = :s';  $par[':s'] = $f['sucursal']; }
        if (!empty($f['fecha_desde'])) { $cond[] = 'h.fecha_hora >= :fd';  $par[':fd'] = $f['fecha_desde'] . ' 00:00:00'; }
        if (!empty($f['fecha_hasta'])) { $cond[] = 'h.fecha_hora <= :fh';  $par[':fh'] = $f['fecha_hasta'] . ' 23:59:59'; }

        return $this->paginar(
            'h.id_historial, h.nro_envio, h.sucursal_cod, s.sucursal_nombre,
             h.estado, est.estado_nombre, h.fecha_hora, h.observacion',
            'historial_estado h
             JOIN sucursal s       ON s.sucursal_cod  = h.sucursal_cod
             JOIN estado_envio est ON est.estado_cod  = h.estado',
            $cond, $par, self::ORDENABLES, $p['orden'], $p['dir'], $p['pagina']
        );
    }

    public function crear(array $h): int
    {
        $this->ejecutar('INSERT INTO historial_estado (nro_envio, sucursal_cod, estado, fecha_hora, observacion)
                         VALUES (:n, :s, :e, :f, :o)',
                        [':n' => $h['nro_envio'], ':s' => $h['sucursal_cod'], ':e' => $h['estado'],
                         ':f' => $h['fecha_hora'], ':o' => $h['observacion']]);
        return (int)$this->pdo->lastInsertId();
    }

    public function actualizar(int $id, array $h): bool
    {
        return $this->ejecutar('UPDATE historial_estado SET nro_envio = :n, sucursal_cod = :s, estado = :e,
                                       fecha_hora = :f, observacion = :o
                                WHERE id_historial = :id',
                               [':n' => $h['nro_envio'], ':s' => $h['sucursal_cod'], ':e' => $h['estado'],
                                ':f' => $h['fecha_hora'], ':o' => $h['observacion'], ':id' => $id]) > 0;
    }

    public function eliminar(int $id): bool
    {
        return $this->ejecutar('DELETE FROM historial_estado WHERE id_historial = :id', [':id' => $id]) > 0;
    }
}
