<?php
/*
 * ============================================================================================
 * QUÉ ES:  el "modelo" de los vehículos (patente, marca, modelo, año y tipo): el ÚNICO lugar con las consultas SQL de esa parte.
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

/** Modelo Vehiculo (baja lógica). */
class Vehiculo extends Modelo
{
    // CAMBIAR: columnas por las que se puede ordenar el listado:
    //          'nombre que manda la pantalla' => 'columna de la base'.
    private const ORDENABLES = [
        'vehiculo_patente' => 'v.vehiculo_patente',
        'marca'            => 'v.marca',
        'modelo'           => 'v.modelo',
        'anio'             => 'v.anio',
        'tipo_veh_nombre'  => 't.tipo_veh_nombre',
    ];

    /** $f: patente, marca, tipo (opcionales). */
    public function listar(array $f, array $p, bool $inactivos): array
    {
        $cond = [];
        $par  = [];
        if (!$inactivos)           { $cond[] = 'v.activo = 1'; }
        if (!empty($f['patente'])) { $cond[] = 'v.vehiculo_patente LIKE :pat'; $par[':pat'] = '%' . $f['patente'] . '%'; }
        if (!empty($f['marca']))   { $cond[] = 'v.marca LIKE :marca';          $par[':marca'] = '%' . $f['marca'] . '%'; }
        if (!empty($f['tipo']))    { $cond[] = 'v.tipo_veh_cod = :tipo';       $par[':tipo'] = $f['tipo']; }

        return $this->paginar(
            'v.vehiculo_patente, v.marca, v.modelo, v.anio, v.tipo_veh_cod, t.tipo_veh_nombre, v.activo',
            'vehiculo v JOIN tipo_vehiculo t ON t.tipo_veh_cod = v.tipo_veh_cod',
            $cond, $par, self::ORDENABLES, $p['orden'], $p['dir'], $p['pagina']
        );
    }

    public function crear(array $v): void
    {
        $this->ejecutar('INSERT INTO vehiculo (vehiculo_patente, marca, modelo, anio, tipo_veh_cod)
                         VALUES (:p, :ma, :mo, :a, :t)',
                        [':p' => $v['vehiculo_patente'], ':ma' => $v['marca'], ':mo' => $v['modelo'],
                         ':a' => $v['anio'], ':t' => $v['tipo_veh_cod']]);
    }

    public function actualizar(string $patente, array $v): bool
    {
        return $this->ejecutar('UPDATE vehiculo SET marca = :ma, modelo = :mo, anio = :a, tipo_veh_cod = :t
                                WHERE vehiculo_patente = :p AND activo = 1',
                               [':ma' => $v['marca'], ':mo' => $v['modelo'], ':a' => $v['anio'],
                                ':t' => $v['tipo_veh_cod'], ':p' => $patente]) > 0;
    }

    /** Baja lógica: se conserva para no perder los viajes que hizo. */
    public function cambiarActivo(string $patente, bool $activo): bool
    {
        return $this->ejecutar('UPDATE vehiculo SET activo = :a WHERE vehiculo_patente = :p',
                               [':a' => (int)$activo, ':p' => $patente]) > 0;
    }
}
