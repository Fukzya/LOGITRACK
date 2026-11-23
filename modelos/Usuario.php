<?php
require_once __DIR__ . '/Modelo.php';

/** Modelo Usuario (cuentas del sistema; baja lógica). La contraseña se guarda solo como hash. */
class Usuario extends Modelo
{
    private const ORDENABLES = ['usuario_id' => 'usuario_id', 'username' => 'username', 'rol' => 'rol'];

    public function listar(array $p): array
    {
        // Nunca se devuelve password_hash.
        return $this->paginar('usuario_id, username, rol, activo', 'usuario', [], [],
                              self::ORDENABLES, $p['orden'], $p['dir'], 1, null);
    }

    public function crear(string $username, string $password, string $rol): int
    {
        $this->ejecutar('INSERT INTO usuario (username, password_hash, rol) VALUES (:u, :h, :r)',
                        [':u' => $username, ':h' => password_hash($password, PASSWORD_DEFAULT), ':r' => $rol]);
        return (int)$this->pdo->lastInsertId();
    }

    public function cambiarRol(int $id, string $rol): bool
    {
        return $this->ejecutar('UPDATE usuario SET rol = :r WHERE usuario_id = :id', [':r' => $rol, ':id' => $id]) > 0;
    }

    public function cambiarPassword(int $id, string $password): bool
    {
        return $this->ejecutar('UPDATE usuario SET password_hash = :h WHERE usuario_id = :id',
                               [':h' => password_hash($password, PASSWORD_DEFAULT), ':id' => $id]) > 0;
    }

    public function cambiarActivo(int $id, bool $activo): bool
    {
        return $this->ejecutar('UPDATE usuario SET activo = :a WHERE usuario_id = :id',
                               [':a' => (int)$activo, ':id' => $id]) > 0;
    }
}
