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

    /** Ejecuta una sentencia preparada y devuelve cuántas filas afectó. */
    protected function ejecutar(string $sql, array $params = []): int
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    /**
     * A filas que traen ultimo_estado_cod (de funcion_ultimo_estado) les agrega el nombre del
     * estado (ultimo_estado) y si es final (terminado), desde el catálogo estado_envio (7 filas).
     * No se hace con un JOIN a estado_envio: la base calcularía la función una vez por cada
     * estado del catálogo, no una por envío.
     */
    protected function conNombreDeEstado(array $filas): array
    {
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
