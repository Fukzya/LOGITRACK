<?php
/*
 * ============================================================================================
 * QUÉ ES:  el "modelo" de las provincias: el ÚNICO lugar con las consultas SQL de esa parte.
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

/** Modelo Provincia (catálogo chico: sin paginación, baja física). */
class Provincia extends Modelo
{
    // CAMBIAR: columnas por las que se puede ordenar el listado:
    //          'nombre que manda la pantalla' => 'columna de la base'.
    private const ORDENABLES = ['provincia_nombre' => 'provincia_nombre', 'provincia_cod' => 'provincia_cod'];

    public function listar(array $p): array
    {
        return $this->paginar('provincia_cod, provincia_nombre', 'provincia', [], [],
                              self::ORDENABLES, $p['orden'], $p['dir'], 1, null);
    }

    public function crear(string $nombre): int
    {
        $this->ejecutar('INSERT INTO provincia (provincia_nombre) VALUES (:n)', [':n' => $nombre]);
        return (int)$this->pdo->lastInsertId();
    }

    public function actualizar(int $cod, string $nombre): bool
    {
        return $this->ejecutar('UPDATE provincia SET provincia_nombre = :n WHERE provincia_cod = :c',
                               [':n' => $nombre, ':c' => $cod]) > 0;
    }

    /** Baja física. Si tiene localidades, la FK lo impide (error 1451). */
    public function eliminar(int $cod): bool
    {
        return $this->ejecutar('DELETE FROM provincia WHERE provincia_cod = :c', [':c' => $cod]) > 0;
    }
}
