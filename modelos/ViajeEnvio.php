<?php
/*
 * ============================================================================================
 * QUÉ ES:  el "modelo" de qué envíos van en qué viaje (asignar y quitar; tramo 2 = trasbordo): el ÚNICO lugar con las consultas SQL de esa parte.
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

/** Modelo ViajeEnvio: tabla puente que dice qué envíos van en cada viaje (relación muchos a muchos). */
class ViajeEnvio extends Modelo
{
    // CAMBIAR: columnas por las que se puede ordenar el listado:
    //          'nombre que manda la pantalla' => 'columna de la base'.
    private const ORDENABLES = [
        'viaje_id'  => 've.viaje_id, ve.nro_envio',
        'nro_envio' => 've.nro_envio, ve.tramo',
    ];

    /** $f: viaje_id, nro_envio (opcionales). */
    public function listar(array $f, array $p): array
    {
        $cond = [];
        $par  = [];
        if (!empty($f['viaje_id']))  { $cond[] = 've.viaje_id = :v';  $par[':v'] = $f['viaje_id']; }
        if (!empty($f['nro_envio'])) { $cond[] = 've.nro_envio = :e'; $par[':e'] = $f['nro_envio']; }

        return $this->paginar(
            "CONCAT(ve.viaje_id, '-', ve.nro_envio) AS asignacion,
             ve.viaje_id, ve.tramo, v.fecha_salida, v.fecha_llegada, ve.nro_envio, e.fecha_envio,
             so.sucursal_completa AS sucursal_orig_nombre, sd.sucursal_completa AS sucursal_dest_nombre,
             vo.sucursal_completa AS viaje_origen, vd.sucursal_completa AS viaje_destino",
            'viaje_envio ve
             JOIN viaje v     ON v.viaje_id      = ve.viaje_id
             JOIN envio e     ON e.nro_envio     = ve.nro_envio
             JOIN vista_sucursal_ubicacion so ON so.sucursal_cod = e.sucursal_orig_cod
             JOIN vista_sucursal_ubicacion sd ON sd.sucursal_cod = e.sucursal_dest_cod
             JOIN vista_sucursal_ubicacion vo ON vo.sucursal_cod = v.sucursal_origen_cod
             JOIN vista_sucursal_ubicacion vd ON vd.sucursal_cod = v.sucursal_destino_cod',
            $cond, $par, self::ORDENABLES, $p['orden'], $p['dir'], $p['pagina']
        );
    }

    /**
     * Asigna con el procedimiento almacenado procedimiento_asignar_envio_a_viaje (rechaza duplicados).
     * Es la única forma de asignar: las cuentas de la app tienen EXECUTE, no INSERT sobre viaje_envio.
     */
    public function asignarConProcedimiento(int $viaje, int $envio): void
    {
        $st = $this->pdo->prepare('CALL procedimiento_asignar_envio_a_viaje(:v, :e)');
        $st->execute([':v' => $viaje, ':e' => $envio]);
        $st->closeCursor(); // el procedimiento devuelve un SELECT con un mensaje: hay que consumirlo
    }

    public function quitar(int $viaje, int $envio): bool
    {
        return $this->ejecutar('DELETE FROM viaje_envio WHERE viaje_id = :v AND nro_envio = :e',
                               [':v' => $viaje, ':e' => $envio]) > 0;
    }
}
