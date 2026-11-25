<?php
/*
 * ============================================================================================
 * QUÉ ES:  el "modelo" de los usuarios que entran a la aplicación y su rol (solo admin): el ÚNICO lugar con las consultas SQL de esa parte.
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

/** Modelo Usuario (cuentas del sistema; baja lógica). La contraseña se guarda solo como hash. */
class Usuario extends Modelo
{
    // CAMBIAR: columnas por las que se puede ordenar el listado:
    //          'nombre que manda la pantalla' => 'columna de la base'.
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
