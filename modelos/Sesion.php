<?php
require_once __DIR__ . '/Modelo.php';

/**
 * Modelo Sesion: las sesiones de la aplicación registradas en la base (tabla sesion, script 011).
 * El vencimiento lo decide la base (funcion_sesion_activa); PHP solo pregunta.
 */
class Sesion extends Modelo
{
    private const ORDENABLES = [
        'fin' => 's.fin', 'inicio' => 's.inicio', 'username' => 'u.username', 'motivo' => 's.motivo_cierre',
    ];

    /** Registra el login y devuelve el id de la sesión nueva. */
    public function iniciar(int $usuarioId, string $tokenHash, ?string $ip, ?string $navegador): int
    {
        $st = $this->pdo->prepare('CALL procedimiento_iniciar_sesion(:u, :t, :ip, :nav, @sesion_id)');
        $st->execute([':u' => $usuarioId, ':t' => $tokenHash, ':ip' => $ip, ':nav' => $navegador]);
        $st->closeCursor();
        return (int)$this->pdo->query('SELECT @sesion_id')->fetchColumn();
    }

    /** Estado de una sesión: token_hash, fin, motivo_cierre y activa (0/1), o null si no existe. */
    public function estado(int $sesionId): ?array
    {
        return $this->fila('SELECT token_hash, fin, motivo_cierre, funcion_sesion_activa(sesion_id) AS activa
                              FROM sesion WHERE sesion_id = :id', [':id' => $sesionId]);
    }

    /** Marca el uso de la sesión (cada pedido) y vincula la conexión con el usuario de la app. */
    public function tocar(int $sesionId, string $username): void
    {
        $this->ejecutar('UPDATE sesion SET ultimo_acceso = NOW() WHERE sesion_id = :id', [':id' => $sesionId]);
        // Variables de la sesión de BASE DE DATOS: solo existen en esta conexión.
        $this->ejecutar('SET @usuario_app = :u, @sesion_app = :s', [':u' => $username, ':s' => $sesionId]);
    }

    public function cerrar(int $sesionId, string $motivo): void
    {
        $this->ejecutar('CALL procedimiento_cerrar_sesion(:id, :m)', [':id' => $sesionId, ':m' => $motivo]);
    }

    public function cerrarVencidas(): void
    {
        $this->ejecutar('CALL procedimiento_cerrar_sesiones_vencidas()');
    }

    /** Sesiones abiertas (vista). */
    public function abiertas(): array
    {
        return $this->pdo->query('SELECT * FROM vista_sesiones_abiertas ORDER BY ultimo_acceso DESC')
                         ->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Sesiones cerradas, paginadas, con filtro opcional por usuario. */
    public function historial(array $p, string $username): array
    {
        $cond = ['s.fin IS NOT NULL'];
        $params = [];
        if ($username !== '') {
            $cond[] = 'u.username LIKE :u';
            $params[':u'] = '%' . $username . '%';
        }
        return $this->paginar(
            's.sesion_id, u.username, s.inicio, s.fin, s.motivo_cierre, s.ip,
             TIMESTAMPDIFF(MINUTE, s.inicio, s.fin) AS minutos',
            'sesion s JOIN usuario u ON u.usuario_id = s.usuario_id',
            $cond, $params, self::ORDENABLES, $p['orden'], $p['dir'], $p['pagina']);
    }

    /** Conexiones abiertas en el motor (cada una es una sesión de base de datos). */
    public function conexiones(): array
    {
        return $this->pdo->query(
            'SELECT ID AS id, USER AS usuario, HOST AS host, DB AS base, COMMAND AS comando,
                    TIME AS segundos, STATE AS estado, LEFT(INFO, 120) AS consulta,
                    ID = CONNECTION_ID() AS propia
               FROM information_schema.PROCESSLIST
              WHERE COMMAND <> \'Daemon\'
              ORDER BY ID')->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Datos de la conexión de este pedido: id, variables de sesión y nivel de aislamiento. */
    public function estaConexion(): array
    {
        return $this->pdo->query('SELECT CONNECTION_ID() AS id, @usuario_app AS usuario_app,
                                         @sesion_app AS sesion_app, @@tx_isolation AS aislamiento')
                         ->fetch(PDO::FETCH_ASSOC);
    }
}
