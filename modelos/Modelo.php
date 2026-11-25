<?php
/*
 * Clase base de los modelos (capa Modelo de MVC).
 * Tiene lo que comparten todas las entidades: la conexión y el listado paginado con filtros y orden.
 * Cada modelo concreto (Cliente, Chofer, Envio...) hereda de acá y escribe su propio SQL.
 */
abstract class Modelo
{
    public const POR_PAGINA = 50;

    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Listado paginado.
     *  $select       columnas del SELECT
     *  $desde        "tabla alias JOIN ..." (lo que va después de FROM)
     *  $condiciones  partes del WHERE (se unen con AND); $params sus valores
     *  $ordenables   nombre público => expresión SQL. El orden pedido se busca acá (lista blanca),
     *                así nunca se arma un ORDER BY con texto que mandó el usuario.
     *  $porPagina    null = sin paginar (catálogos chicos)
     */
    protected function paginar(string $select, string $desde, array $condiciones, array $params,
                               array $ordenables, string $orden, string $dir, int $pagina,
                               ?int $porPagina = self::POR_PAGINA): array
    {
        $where   = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';
        $ordenSql = $ordenables[$orden] ?? reset($ordenables);
        $dir     = $dir === 'DESC' ? 'DESC' : 'ASC';

        $st = $this->pdo->prepare("SELECT COUNT(*) FROM $desde $where");
        $st->execute($params);
        $total = (int)$st->fetchColumn();

        $limite = '';
        $pagina = max(1, $pagina);
        if ($porPagina !== null) {
            $limite = 'LIMIT ' . $porPagina . ' OFFSET ' . (($pagina - 1) * $porPagina);
        }
        $st = $this->pdo->prepare("SELECT $select FROM $desde $where ORDER BY $ordenSql $dir $limite");
        $st->execute($params);

        return [
            'datos'         => $st->fetchAll(PDO::FETCH_ASSOC),
            'total'         => $total,
            'pagina'        => $porPagina === null ? 1 : $pagina,
            'total_paginas' => $porPagina === null ? 1 : max(1, (int)ceil($total / $porPagina)),
        ];
    }

    /**
     * Listado paginado de una tabla grande que se muestra a través de una vista (Envíos, Historial).
     * Mismo resultado que paginar(), pero pensado para muchas filas:
     *   1. cuenta y elige las claves de la página en la TABLA, con su alias y sus índices;
     *   2. trae de la VISTA solo esas filas (50).
     * Con paginar() sobre la vista, la base hacía todos los JOIN para contar y ordenaba todas las
     * filas para mostrar 50: con 100.000 envíos, 1,9 s; así, unos 0,15 s.
     *  $tabla        "envio v": tabla y alias que usan $condiciones y $ordenables
     *  $clave        clave primaria ("nro_envio"), igual en la tabla y en la vista
     *  $vista        vista que arma los JOIN (devuelve la columna $clave)
     */
    protected function paginarPorClave(string $tabla, string $clave, string $vista, array $condiciones,
                                       array $params, array $ordenables, string $orden, string $dir,
                                       int $pagina): array
    {
        $where    = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';
        $ordenSql = $ordenables[$orden] ?? reset($ordenables);
        $dir      = $dir === 'DESC' ? 'DESC' : 'ASC';
        $alias    = preg_split('/\s+/', trim($tabla))[1] ?? $tabla;
        $pagina   = max(1, $pagina);

        $st = $this->pdo->prepare("SELECT COUNT(*) FROM $tabla $where");
        $st->execute($params);
        $total = (int)$st->fetchColumn();

        // La clave al final del ORDER BY: si hay empates (misma fecha), el orden no cambia entre páginas.
        $st = $this->pdo->prepare("SELECT $alias.$clave FROM $tabla $where
                                   ORDER BY $ordenSql $dir, $alias.$clave $dir
                                   LIMIT " . self::POR_PAGINA . ' OFFSET ' . (($pagina - 1) * self::POR_PAGINA));
        $st->execute($params);
        $claves = $st->fetchAll(PDO::FETCH_COLUMN);

        $datos = [];
        if ($claves) {
            $marcas = implode(',', array_fill(0, count($claves), '?'));
            $st = $this->pdo->prepare("SELECT * FROM $vista WHERE $clave IN ($marcas)");
            $st->execute($claves);
            $porClave = [];
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $fila) $porClave[$fila[$clave]] = $fila;
            foreach ($claves as $k) if (isset($porClave[$k])) $datos[] = $porClave[$k];   // el orden de la página
        }

        return [
            'datos'         => $datos,
            'total'         => $total,
            'pagina'        => $pagina,
            'total_paginas' => max(1, (int)ceil($total / self::POR_PAGINA)),
        ];
    }

    /** Ejecuta una sentencia preparada y devuelve cuántas filas afectó. */
    protected function ejecutar(string $sql, array $params = []): int
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    /**
     * A filas de envíos (con nro_envio) les agrega ultimo_estado_cod (funcion_ultimo_estado, si no
     * lo traen), el nombre del estado (ultimo_estado) y si es final (terminado).
     * - La función se calcula en una consulta aparte, solo para estas filas: dentro de un listado
     *   ordenado, la base la calculaba para todos los envíos antes de cortar la página.
     * - El nombre sale del catálogo estado_envio (7 filas) y no de un JOIN: con
     *   JOIN ... ON estado_cod = funcion(...) la base la calcularía una vez por estado del catálogo.
     */
    protected function conNombreDeEstado(array $filas): array
    {
        $faltan = array_column(array_filter($filas, fn($f) => !array_key_exists('ultimo_estado_cod', $f)), 'nro_envio');
        if ($faltan) {
            $marcas = implode(',', array_fill(0, count($faltan), '?'));
            $st = $this->pdo->prepare("SELECT nro_envio, funcion_ultimo_estado(nro_envio) FROM envio WHERE nro_envio IN ($marcas)");
            $st->execute(array_map('intval', $faltan));
            $codigos = $st->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach ($filas as &$fila) {
                if (!array_key_exists('ultimo_estado_cod', $fila)) $fila['ultimo_estado_cod'] = $codigos[$fila['nro_envio']] ?? null;
            }
            unset($fila);
        }
        $estados = $this->pdo->query('SELECT estado_cod, estado_nombre, es_final FROM estado_envio')
                             ->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
        foreach ($filas as &$fila) {
            $est = $estados[$fila['ultimo_estado_cod']] ?? null;
            $fila['ultimo_estado'] = $est['estado_nombre'] ?? null;
            $fila['terminado']     = $est ? (int)$est['es_final'] : 0;
        }
        unset($fila);
        return $filas;
    }

    /** Primera fila de una consulta, o null. */
    protected function fila(string $sql, array $params = []): ?array
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
