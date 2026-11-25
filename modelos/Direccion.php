<?php
/*
 * ============================================================================================
 * QUÉ ES:  el "modelo" de las direcciones de entrega de cada cliente: el ÚNICO lugar con las consultas SQL de esa parte.
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

/** Modelo Direccion (baja lógica). */
class Direccion extends Modelo
{
    // CAMBIAR: columnas por las que se puede ordenar el listado:
    //          'nombre que manda la pantalla' => 'columna de la base'.
    private const ORDENABLES = [
        'direccion_id'     => 'd.direccion_id',
        'calle_y_numero'   => 'd.calle_y_numero',
        'localidad_nombre' => 'l.localidad_nombre',
        'cliente_dni'      => 'd.cliente_dni',
    ];

    /** $f: calle, cliente_dni, localidad (opcionales). */
    public function listar(array $f, array $p, bool $inactivos): array
    {
        $cond = [];
        $par  = [];
        if (!$inactivos)              { $cond[] = 'd.activo = 1'; }
        if (!empty($f['calle']))       { $cond[] = 'd.calle_y_numero LIKE :calle'; $par[':calle'] = '%' . $f['calle'] . '%'; }
        if (!empty($f['cliente_dni'])) { $cond[] = 'd.cliente_dni = :dni';         $par[':dni'] = $f['cliente_dni']; }
        if (!empty($f['localidad']))   { $cond[] = 'd.localidad_cod = :loc';       $par[':loc'] = $f['localidad']; }

        return $this->paginar(
            "d.direccion_id, d.calle_y_numero, d.localidad_cod, l.localidad_nombre, l.provincia_cod,
             d.cliente_dni, CONCAT(c.nombre, ' ', c.apellido) AS cliente_nombre, d.activo",
            'direccion d JOIN localidad l ON l.localidad_cod = d.localidad_cod
                         JOIN cliente c   ON c.cliente_dni   = d.cliente_dni',
            $cond, $par, self::ORDENABLES, $p['orden'], $p['dir'], $p['pagina']
        );
    }

    /** DNI del cliente dueño de una dirección activa, o null si no existe o está dada de baja. */
    public function dueno(int $id): ?int
    {
        $f = $this->fila('SELECT cliente_dni FROM direccion WHERE direccion_id = :id AND activo = 1', [':id' => $id]);
        return $f ? (int)$f['cliente_dni'] : null;
    }

    public function crear(array $d): int
    {
        $this->ejecutar('INSERT INTO direccion (calle_y_numero, localidad_cod, cliente_dni) VALUES (:c, :l, :dni)',
                        [':c' => $d['calle_y_numero'], ':l' => $d['localidad_cod'], ':dni' => $d['cliente_dni']]);
        return (int)$this->pdo->lastInsertId();
    }

    public function actualizar(int $id, array $d): bool
    {
        return $this->ejecutar('UPDATE direccion SET calle_y_numero = :c, localidad_cod = :l, cliente_dni = :dni
                                WHERE direccion_id = :id AND activo = 1',
                               [':c' => $d['calle_y_numero'], ':l' => $d['localidad_cod'],
                                ':dni' => $d['cliente_dni'], ':id' => $id]) > 0;
    }

    /** Baja lógica: los envíos ya entregados en esa dirección la siguen referenciando. */
    public function cambiarActivo(int $id, bool $activo): bool
    {
        return $this->ejecutar('UPDATE direccion SET activo = :a WHERE direccion_id = :id',
                               [':a' => (int)$activo, ':id' => $id]) > 0;
    }
}
