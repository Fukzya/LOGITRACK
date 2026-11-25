<?php
/*
 * ============================================================================================
 * QUÉ ES:  el "modelo" de los tipos de contenido de un envío (estándar, frágil, peligroso): el ÚNICO lugar con las consultas SQL de esa parte.
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

/** Modelo TipoContenido (catálogo: sin paginación, baja física). */
class TipoContenido extends Modelo
{
    // CAMBIAR: columnas por las que se puede ordenar el listado:
    //          'nombre que manda la pantalla' => 'columna de la base'.
    private const ORDENABLES = ['tipo_cont_cod' => 'tipo_cont_cod', 'tipo_cont_nombre' => 'tipo_cont_nombre'];

    public function listar(array $p): array
    {
        return $this->paginar('tipo_cont_cod, tipo_cont_nombre, tipo_cont_requiere_seguro', 'tipo_contenido',
                              [], [], self::ORDENABLES, $p['orden'], $p['dir'], 1, null);
    }

    public function crear(array $t): void
    {
        $this->ejecutar('INSERT INTO tipo_contenido (tipo_cont_cod, tipo_cont_nombre, tipo_cont_requiere_seguro)
                         VALUES (:c, :n, :s)',
                        [':c' => $t['tipo_cont_cod'], ':n' => $t['tipo_cont_nombre'], ':s' => $t['tipo_cont_requiere_seguro']]);
    }

    public function actualizar(string $cod, array $t): bool
    {
        return $this->ejecutar('UPDATE tipo_contenido SET tipo_cont_nombre = :n, tipo_cont_requiere_seguro = :s
                                WHERE tipo_cont_cod = :c',
                               [':n' => $t['tipo_cont_nombre'], ':s' => $t['tipo_cont_requiere_seguro'], ':c' => $cod]) > 0;
    }

    /** Baja física. Si hay envíos de este tipo, la FK lo impide (1451). */
    public function eliminar(string $cod): bool
    {
        return $this->ejecutar('DELETE FROM tipo_contenido WHERE tipo_cont_cod = :c', [':c' => $cod]) > 0;
    }
}
