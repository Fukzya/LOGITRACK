<?php
require_once __DIR__ . '/Modelo.php';

/** Modelo HistorialEstado: los estados por los que pasa cada envío (registro inmutable: solo altas). */
class HistorialEstado extends Modelo
{
    private const ORDENABLES = [
        'id_historial' => 'h.id_historial',
        'nro_envio'    => 'h.nro_envio, h.fecha_hora',
        'fecha_hora'   => 'h.fecha_hora',
    ];

    /** $f: nro_envio, estado, sucursal, fecha_desde, fecha_hasta (opcionales). */
    public function listar(array $f, array $p): array
    {
        $cond = [];
        $par  = [];
        if (!empty($f['nro_envio']))   { $cond[] = 'h.nro_envio = :n';     $par[':n'] = $f['nro_envio']; }
        if (!empty($f['estado']))      { $cond[] = 'h.estado = :estado';   $par[':estado'] = $f['estado']; }
        if (!empty($f['sucursal']))    { $cond[] = 'h.sucursal_cod = :s';  $par[':s'] = $f['sucursal']; }
        if (!empty($f['fecha_desde'])) { $cond[] = 'h.fecha_hora >= :fd';  $par[':fd'] = $f['fecha_desde'] . ' 00:00:00'; }
        if (!empty($f['fecha_hasta'])) { $cond[] = 'h.fecha_hora <= :fh';  $par[':fh'] = $f['fecha_hasta'] . ' 23:59:59'; }

        // La vista vista_historial_completo arma los JOIN (sucursal con provincia, estado, clientes).
        return $this->paginar('h.*', 'vista_historial_completo h',
                              $cond, $par, self::ORDENABLES, $p['orden'], $p['dir'], $p['pagina']);
    }

    /**
     * Registra un estado con el procedimiento procedimiento_registrar_estado_envio (valida que el
     * envío, la sucursal y el estado existan). Sin fecha usa NOW() de la base. Devuelve el id
     * que el procedimiento deja en su parámetro OUT.
     */
    public function crear(array $h): int
    {
        $this->ejecutar('CALL procedimiento_registrar_estado_envio(:n, :s, :e, :f, :o, @id_historial)',
                        [':n' => $h['nro_envio'], ':s' => $h['sucursal_cod'], ':e' => $h['estado'],
                         ':f' => $h['fecha_hora'], ':o' => $h['observacion']]);
        return (int)$this->pdo->query('SELECT @id_historial')->fetchColumn();
    }

    // No hay actualizar() ni eliminar(): el historial es inmutable. Un cambio de estado es
    // siempre una fila nueva, así queda registrado todo el recorrido del envío.
}
