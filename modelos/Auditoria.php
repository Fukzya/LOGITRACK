<?php
require_once __DIR__ . '/Modelo.php';

/**
 * Modelo Auditoria: solo lectura. Las filas las escriben los triggers AFTER de envio, viaje y
 * usuario (script 013), nunca la aplicación.
 */
class Auditoria extends Modelo
{
    private const ORDENABLES = [
        'auditoria_id' => 'a.auditoria_id',
        'fecha_hora'   => 'a.fecha_hora, a.auditoria_id',
    ];

    /** $f: tabla, clave, usuario, fecha_desde, fecha_hasta (opcionales). */
    public function listar(array $f, array $p): array
    {
        $cond = [];
        $par  = [];
        if (!empty($f['tabla']))       { $cond[] = 'a.tabla = :t';         $par[':t'] = $f['tabla']; }
        if (!empty($f['clave']))       { $cond[] = 'a.clave = :c';         $par[':c'] = $f['clave']; }
        if (!empty($f['usuario']))     { $cond[] = 'a.usuario LIKE :u';    $par[':u'] = '%' . $f['usuario'] . '%'; }
        if (!empty($f['fecha_desde'])) { $cond[] = 'a.fecha_hora >= :fd';  $par[':fd'] = $f['fecha_desde'] . ' 00:00:00'; }
        if (!empty($f['fecha_hasta'])) { $cond[] = 'a.fecha_hora <= :fh';  $par[':fh'] = $f['fecha_hasta'] . ' 23:59:59'; }

        return $this->paginar(
            'a.auditoria_id, a.tabla, a.clave, a.operacion, a.campo, a.valor_anterior, a.valor_nuevo, a.usuario, a.fecha_hora',
            'auditoria a',
            $cond, $par, self::ORDENABLES, $p['orden'], $p['dir'], $p['pagina']
        );
    }
}
