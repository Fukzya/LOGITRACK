<?php
require_once __DIR__ . '/Modelo.php';

/** Modelo TipoVehiculo (catálogo: sin paginación, baja física). */
class TipoVehiculo extends Modelo
{
    private const ORDENABLES = [
        'tipo_veh_cod'          => 'tipo_veh_cod',
        'tipo_veh_nombre'       => 'tipo_veh_nombre',
        'tipo_veh_capacidad_kg' => 'tipo_veh_capacidad_kg',
    ];

    public function listar(array $p): array
    {
        return $this->paginar('tipo_veh_cod, tipo_veh_nombre, tipo_veh_capacidad_kg, tipo_veh_requiere_lic_especial',
                              'tipo_vehiculo', [], [], self::ORDENABLES, $p['orden'], $p['dir'], 1, null);
    }

    public function crear(array $t): void
    {
        $this->ejecutar('INSERT INTO tipo_vehiculo (tipo_veh_cod, tipo_veh_nombre, tipo_veh_capacidad_kg, tipo_veh_requiere_lic_especial)
                         VALUES (:c, :n, :k, :l)',
                        [':c' => $t['tipo_veh_cod'], ':n' => $t['tipo_veh_nombre'],
                         ':k' => $t['tipo_veh_capacidad_kg'], ':l' => $t['tipo_veh_requiere_lic_especial']]);
    }

    public function actualizar(string $cod, array $t): bool
    {
        return $this->ejecutar('UPDATE tipo_vehiculo SET tipo_veh_nombre = :n, tipo_veh_capacidad_kg = :k,
                                       tipo_veh_requiere_lic_especial = :l
                                WHERE tipo_veh_cod = :c',
                               [':n' => $t['tipo_veh_nombre'], ':k' => $t['tipo_veh_capacidad_kg'],
                                ':l' => $t['tipo_veh_requiere_lic_especial'], ':c' => $cod]) > 0;
    }

    /** Baja física. Si hay vehículos de este tipo, la FK lo impide (1451). */
    public function eliminar(string $cod): bool
    {
        return $this->ejecutar('DELETE FROM tipo_vehiculo WHERE tipo_veh_cod = :c', [':c' => $cod]) > 0;
    }
}
