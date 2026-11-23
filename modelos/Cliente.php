<?php
/*
 * Modelo Cliente (capa Modelo de MVC).
 * Es el único lugar donde está el SQL de la tabla cliente. No sabe nada de HTTP ni de sesiones:
 * recibe datos ya validados por el controlador (api/clientes.php) y devuelve arrays.
 */
class Cliente
{
    /** Columnas por las que se permite ordenar (lista blanca: evita inyección en ORDER BY). */
    public const ORDENABLES = ['cliente_dni', 'nombre', 'apellido', 'localidad_nombre'];
    private const POR_PAGINA = 50;

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Listado paginado con filtros.
     * $filtros: dni, nombre, apellido (opcionales); $inactivos: incluir dados de baja.
     */
    public function listar(array $filtros, int $pagina, string $orden, string $dir, bool $inactivos): array
    {
        $condiciones = [];
        $params      = [];

        if (!$inactivos) {
            $condiciones[] = 'c.activo = 1';
        }
        if (!empty($filtros['dni'])) {
            $condiciones[] = 'c.cliente_dni = :dni';
            $params[':dni'] = $filtros['dni'];
        }
        if (!empty($filtros['nombre'])) {
            $condiciones[] = 'c.nombre LIKE :nombre';
            $params[':nombre'] = '%' . $filtros['nombre'] . '%';
        }
        if (!empty($filtros['apellido'])) {
            $condiciones[] = 'c.apellido LIKE :apellido';
            $params[':apellido'] = '%' . $filtros['apellido'] . '%';
        }
        $where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

        $orden  = in_array($orden, self::ORDENABLES, true) ? $orden : 'cliente_dni';
        $dir    = $dir === 'DESC' ? 'DESC' : 'ASC';
        $pagina = max(1, $pagina);
        $offset = ($pagina - 1) * self::POR_PAGINA;

        $st = $this->pdo->prepare("SELECT COUNT(*) FROM cliente c $where");
        $st->execute($params);
        $total = (int)$st->fetchColumn();

        $st = $this->pdo->prepare(
            "SELECT c.cliente_dni, c.nombre, c.apellido, c.mail, c.telefono,
                    c.localidad_cod, l.localidad_nombre, c.activo
             FROM cliente c
             JOIN localidad l ON l.localidad_cod = c.localidad_cod
             $where
             ORDER BY $orden $dir
             LIMIT " . self::POR_PAGINA . " OFFSET $offset"
        );
        $st->execute($params);

        return [
            'datos'         => $st->fetchAll(PDO::FETCH_ASSOC),
            'total'         => $total,
            'pagina'        => $pagina,
            'total_paginas' => max(1, (int)ceil($total / self::POR_PAGINA)),
        ];
    }

    public function buscarPorDni(int $dni): ?array
    {
        $st = $this->pdo->prepare('SELECT * FROM cliente WHERE cliente_dni = :dni');
        $st->execute([':dni' => $dni]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function crear(array $c): void
    {
        $st = $this->pdo->prepare(
            'INSERT INTO cliente (cliente_dni, nombre, apellido, mail, telefono, localidad_cod)
             VALUES (:dni, :nombre, :apellido, :mail, :telefono, :localidad)'
        );
        $st->execute([
            ':dni'       => $c['cliente_dni'],
            ':nombre'    => $c['nombre'],
            ':apellido'  => $c['apellido'],
            ':mail'      => $c['mail'],
            ':telefono'  => $c['telefono'],
            ':localidad' => $c['localidad_cod'],
        ]);
    }

    /** Devuelve false si no existe un cliente activo con ese DNI. */
    public function actualizar(int $dni, array $c): bool
    {
        $actual = $this->buscarPorDni($dni);
        if ($actual === null || (int)$actual['activo'] !== 1) {
            return false;
        }
        $st = $this->pdo->prepare(
            'UPDATE cliente
             SET nombre = :nombre, apellido = :apellido, mail = :mail,
                 telefono = :telefono, localidad_cod = :localidad
             WHERE cliente_dni = :dni'
        );
        $st->execute([
            ':nombre'    => $c['nombre'],
            ':apellido'  => $c['apellido'],
            ':mail'      => $c['mail'],
            ':telefono'  => $c['telefono'],
            ':localidad' => $c['localidad_cod'],
            ':dni'       => $dni,
        ]);
        return true;
    }

    /** Baja lógica: el cliente no se borra para no perder sus envíos y direcciones. */
    public function darDeBaja(int $dni): bool
    {
        return $this->cambiarActivo($dni, 0);
    }

    public function reactivar(int $dni): bool
    {
        return $this->cambiarActivo($dni, 1);
    }

    private function cambiarActivo(int $dni, int $activo): bool
    {
        $st = $this->pdo->prepare('UPDATE cliente SET activo = :activo WHERE cliente_dni = :dni');
        $st->execute([':activo' => $activo, ':dni' => $dni]);
        return $this->buscarPorDni($dni) !== null;
    }
}
