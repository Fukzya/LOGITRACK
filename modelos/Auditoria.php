<?php
/*
 * ============================================================================================
 * QUÉ ES:  el "modelo" de el registro de cambios (quién cambió qué y cuándo; solo admin): el ÚNICO lugar con las consultas SQL de esa parte.
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

/**
 * Modelo Auditoria: solo lectura. Las filas las escriben los triggers AFTER de envio, viaje y
 * usuario (script 013), nunca la aplicación.
 */
class Auditoria extends Modelo
{
    // CAMBIAR: columnas por las que se puede ordenar el listado:
    //          'nombre que manda la pantalla' => 'columna de la base'.
    private const ORDENABLES = [
        'auditoria_id' => 'a.auditoria_id',
        'fecha_hora'   => 'a.fecha_hora, a.auditoria_id',
    ];

    /** $f: tabla, clave, usuario, fecha_desde, fecha_hasta (opcionales). */
    public function listar(array $f, array $p): array
    {
        $cond = [];
        $par  = [];
        if (!empty($f['tabla']))       { $cond[] = 'a.tabla = :t';         $par[':t'] = $f['tabla']; }
        if (!empty($f['clave']))       { $cond[] = 'a.clave = :c';         $par[':c'] = $f['clave']; }
        if (!empty($f['usuario']))     { $cond[] = 'a.usuario LIKE :u';    $par[':u'] = '%' . $f['usuario'] . '%'; }
        if (!empty($f['fecha_desde'])) { $cond[] = 'a.fecha_hora >= :fd';  $par[':fd'] = $f['fecha_desde'] . ' 00:00:00'; }
        if (!empty($f['fecha_hasta'])) { $cond[] = 'a.fecha_hora <= :fh';  $par[':fh'] = $f['fecha_hasta'] . ' 23:59:59'; }

        return $this->paginar(
            'a.auditoria_id, a.tabla, a.clave, a.operacion, a.campo, a.valor_anterior, a.valor_nuevo, a.usuario, a.fecha_hora',
            'auditoria a',
            $cond, $par, self::ORDENABLES, $p['orden'], $p['dir'], $p['pagina']
        );
    }
}
