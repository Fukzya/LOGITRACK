<?php
require_once __DIR__ . '/Modelo.php';

/** Modelo Sucursal (baja lógica). */
class Sucursal extends Modelo
{
    private const ORDENABLES = [
        'sucursal_cod'     => 's.sucursal_cod',
        'sucursal_nombre'  => 's.sucursal_nombre',
        'localidad_nombre' => 'l.localidad_nombre',
        'provincia_nombre' => 'p.provincia_nombre, l.localidad_nombre',
    ];

    /** $f: nombre (opcional). */
    public function listar(array $f, array $p, bool $inactivos): array
    {
        $cond = [];
        $par  = [];
        if (!$inactivos)         { $cond[] = 's.activo = 1'; }
        if (!empty($f['nombre'])) { $cond[] = 's.sucursal_nombre LIKE :n'; $par[':n'] = '%' . $f['nombre'] . '%'; }

        return $this->paginar(
            's.sucursal_cod, s.sucursal_nombre, s.sucursal_localidad_cod, l.localidad_nombre, p.provincia_nombre, s.direccion, s.activo',
            'sucursal s JOIN localidad l ON l.localidad_cod = s.sucursal_localidad_cod
                        JOIN provincia p ON p.provincia_cod = l.provincia_cod',
            $cond, $par, self::ORDENABLES, $p['orden'], $p['dir'], $p['pagina']
        );
    }

    /** Resumen por sucursal en un período, con procedimiento_resumen_por_sucursal (null = sin límite). */
    public function resumen(?string $desde, ?string $hasta): array
    {
        $st = $this->pdo->prepare('CALL procedimiento_resumen_por_sucursal(:d, :h)');
        $st->execute([':d' => $desde, ':h' => $hasta]);
        $filas = $st->fetchAll(PDO::FETCH_ASSOC);
        $st->closeCursor();
        return $filas;
    }

    /** Provincia donde está una sucursal activa (null si no existe o está dada de baja). */
    public function provincia(string $cod): ?int
    {
        $f = $this->fila('SELECT provincia_cod FROM vista_sucursal_ubicacion WHERE sucursal_cod = :cod AND activo = 1',
                         [':cod' => $cod]);
        return $f ? (int)$f['provincia_cod'] : null;
    }

    public function crear(array $s): void
    {
        $this->ejecutar('INSERT INTO sucursal (sucursal_cod, sucursal_nombre, sucursal_localidad_cod, direccion)
                         VALUES (:c, :n, :l, :d)',
                        [':c' => $s['sucursal_cod'], ':n' => $s['sucursal_nombre'],
                         ':l' => $s['sucursal_localidad_cod'], ':d' => $s['direccion']]);
    }

    public function actualizar(string $cod, array $s): bool
    {
        return $this->ejecutar('UPDATE sucursal SET sucursal_nombre = :n, sucursal_localidad_cod = :l, direccion = :d
                                WHERE sucursal_cod = :c AND activo = 1',
                               [':n' => $s['sucursal_nombre'], ':l' => $s['sucursal_localidad_cod'],
                                ':d' => $s['direccion'], ':c' => $cod]) > 0;
    }

    /** Baja lógica: se conserva para no perder el historial de los envíos que pasaron por ella. */
    public function cambiarActivo(string $cod, bool $activo): bool
    {
        return $this->ejecutar('UPDATE sucursal SET activo = :a WHERE sucursal_cod = :c',
                               [':a' => (int)$activo, ':c' => $cod]) > 0;
    }
}
