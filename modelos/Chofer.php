<?php
require_once __DIR__ . '/Modelo.php';

/** Modelo Chofer (baja lógica). */
class Chofer extends Modelo
{
    private const ORDENABLES = [
        'chofer_legajo'        => 'c.chofer_legajo',
        'nombre'               => 'c.nombre',
        'apellido'             => 'c.apellido',
        'chofer_tipo_licencia' => 'c.chofer_tipo_licencia',
    ];

    /** $f: nombre, apellido, licencia (opcionales). */
    public function listar(array $f, array $p, bool $inactivos): array
    {
        $cond = [];
        $par  = [];
        if (!$inactivos)            { $cond[] = 'c.activo = 1'; }
        if (!empty($f['nombre']))   { $cond[] = 'c.nombre LIKE :nombre';       $par[':nombre'] = '%' . $f['nombre'] . '%'; }
        if (!empty($f['apellido'])) { $cond[] = 'c.apellido LIKE :apellido';   $par[':apellido'] = '%' . $f['apellido'] . '%'; }
        if (!empty($f['licencia'])) { $cond[] = 'c.chofer_tipo_licencia = :l'; $par[':l'] = $f['licencia']; }

        return $this->paginar(
            'c.chofer_legajo, c.nombre, c.apellido, c.chofer_tipo_licencia, t.licencia_descripcion, c.activo',
            'chofer c JOIN tipo_licencia t ON t.licencia_cod = c.chofer_tipo_licencia',
            $cond, $par, self::ORDENABLES, $p['orden'], $p['dir'], $p['pagina']
        );
    }

    public function crear(array $c): int
    {
        $this->ejecutar('INSERT INTO chofer (nombre, apellido, chofer_tipo_licencia) VALUES (:n, :a, :l)',
                        [':n' => $c['nombre'], ':a' => $c['apellido'], ':l' => $c['chofer_tipo_licencia']]);
        return (int)$this->pdo->lastInsertId();
    }

    public function actualizar(int $legajo, array $c): bool
    {
        return $this->ejecutar('UPDATE chofer SET nombre = :n, apellido = :a, chofer_tipo_licencia = :l
                                WHERE chofer_legajo = :leg AND activo = 1',
                               [':n' => $c['nombre'], ':a' => $c['apellido'], ':l' => $c['chofer_tipo_licencia'],
                                ':leg' => $legajo]) > 0;
    }

    /** Baja lógica: se conserva para no perder los viajes que hizo. */
    public function cambiarActivo(int $legajo, bool $activo): bool
    {
        return $this->ejecutar('UPDATE chofer SET activo = :a WHERE chofer_legajo = :leg',
                               [':a' => (int)$activo, ':leg' => $legajo]) > 0;
    }
}
