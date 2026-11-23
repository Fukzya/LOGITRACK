<?php
require_once __DIR__ . '/Modelo.php';

/** Modelo Localidad (catálogo: sin paginación, baja física). */
class Localidad extends Modelo
{
    private const ORDENABLES = [
        'provincia_nombre' => 'p.provincia_nombre, l.localidad_nombre',
        'localidad_nombre' => 'l.localidad_nombre',
        'localidad_cod'    => 'l.localidad_cod',
    ];

    /** $provincia: filtrar por provincia (opcional). */
    public function listar(?int $provincia, array $p): array
    {
        $cond = $provincia ? ['l.provincia_cod = :p'] : [];
        $par  = $provincia ? [':p' => $provincia] : [];
        return $this->paginar(
            'l.localidad_cod, l.localidad_nombre, l.provincia_cod, p.provincia_nombre',
            'localidad l JOIN provincia p ON p.provincia_cod = l.provincia_cod',
            $cond, $par, self::ORDENABLES, $p['orden'], $p['dir'], 1, null
        );
    }

    public function crear(array $l): int
    {
        $this->ejecutar('INSERT INTO localidad (localidad_nombre, provincia_cod) VALUES (:n, :p)',
                        [':n' => $l['localidad_nombre'], ':p' => $l['provincia_cod']]);
        return (int)$this->pdo->lastInsertId();
    }

    public function actualizar(int $cod, array $l): bool
    {
        return $this->ejecutar('UPDATE localidad SET localidad_nombre = :n, provincia_cod = :p WHERE localidad_cod = :c',
                               [':n' => $l['localidad_nombre'], ':p' => $l['provincia_cod'], ':c' => $cod]) > 0;
    }

    /** Baja física. Si la usan clientes, direcciones o sucursales, la FK lo impide (1451). */
    public function eliminar(int $cod): bool
    {
        return $this->ejecutar('DELETE FROM localidad WHERE localidad_cod = :c', [':c' => $cod]) > 0;
    }
}
