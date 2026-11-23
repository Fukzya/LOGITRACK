<?php
require_once __DIR__ . '/Modelo.php';

/** Modelo Provincia (catálogo chico: sin paginación, baja física). */
class Provincia extends Modelo
{
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
