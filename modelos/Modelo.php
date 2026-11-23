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

    /** Primera fila de una consulta, o null. */
    protected function fila(string $sql, array $params = []): ?array
    {
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
