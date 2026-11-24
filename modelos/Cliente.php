<?php
require_once __DIR__ . '/Modelo.php';

/** Modelo Cliente: el único lugar con SQL de la tabla cliente. */
class Cliente extends Modelo
{
    private const ORDENABLES = [
        'cliente_dni'      => 'c.cliente_dni',
        'nombre'           => 'c.nombre',
        'apellido'         => 'c.apellido',
        'localidad_nombre' => 'l.localidad_nombre',
    ];

    /** $f: dni, nombre, apellido, buscar (opcionales). buscar = DNI que empieza con el texto o nombre/apellido que lo contiene. */
    public function listar(array $f, array $p, bool $inactivos): array
    {
        $cond = [];
        $par  = [];
        if (!$inactivos)           { $cond[] = 'c.activo = 1'; }
        if (!empty($f['dni']))      { $cond[] = 'c.cliente_dni = :dni';     $par[':dni'] = $f['dni']; }
        if (!empty($f['nombre']))   { $cond[] = 'c.nombre LIKE :nombre';     $par[':nombre'] = '%' . $f['nombre'] . '%'; }
        if (!empty($f['apellido'])) { $cond[] = 'c.apellido LIKE :apellido'; $par[':apellido'] = '%' . $f['apellido'] . '%'; }
        if (!empty($f['buscar'])) {
            $cond[] = "(CAST(c.cliente_dni AS CHAR) LIKE :b_dni
                        OR CONCAT(c.nombre, ' ', c.apellido) LIKE :b_txt1
                        OR CONCAT(c.apellido, ' ', c.nombre) LIKE :b_txt2)";
            $par[':b_dni']  = $f['buscar'] . '%';
            $par[':b_txt1'] = $par[':b_txt2'] = '%' . $f['buscar'] . '%';
        }

        return $this->paginar(
            'c.cliente_dni, c.nombre, c.apellido, c.mail, c.telefono, c.localidad_cod, l.localidad_nombre, l.provincia_cod, c.activo',
            'cliente c JOIN localidad l ON l.localidad_cod = c.localidad_cod',
            $cond, $par, self::ORDENABLES, $p['orden'], $p['dir'], $p['pagina']
        );
    }

    public function buscar(int $dni): ?array
    {
        return $this->fila('SELECT * FROM cliente WHERE cliente_dni = :dni', [':dni' => $dni]);
    }

    public function crear(array $c): void
    {
        $this->ejecutar(
            'INSERT INTO cliente (cliente_dni, nombre, apellido, mail, telefono, localidad_cod)
             VALUES (:dni, :nombre, :apellido, :mail, :telefono, :localidad)',
            [':dni' => $c['cliente_dni'], ':nombre' => $c['nombre'], ':apellido' => $c['apellido'],
             ':mail' => $c['mail'], ':telefono' => $c['telefono'], ':localidad' => $c['localidad_cod']]
        );
    }

    /** Solo se editan clientes activos. Devuelve false si no hay uno activo con ese DNI. */
    public function actualizar(int $dni, array $c): bool
    {
        return $this->ejecutar(
            'UPDATE cliente SET nombre = :nombre, apellido = :apellido, mail = :mail,
                                telefono = :telefono, localidad_cod = :localidad
             WHERE cliente_dni = :dni AND activo = 1',
            [':nombre' => $c['nombre'], ':apellido' => $c['apellido'], ':mail' => $c['mail'],
             ':telefono' => $c['telefono'], ':localidad' => $c['localidad_cod'], ':dni' => $dni]
        ) > 0;
    }

    /** Baja lógica: no se borra para no perder sus envíos y direcciones. */
    public function cambiarActivo(int $dni, bool $activo): bool
    {
        return $this->ejecutar('UPDATE cliente SET activo = :a WHERE cliente_dni = :dni',
                               [':a' => (int)$activo, ':dni' => $dni]) > 0;
    }
}
