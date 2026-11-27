<?php
/*
 * ============================================================================================
 * QUÉ ES:  el "modelo" de los envíos (paquetes): peso, contenido, remitente, destinatario y sucursales: el ÚNICO lugar con las consultas SQL de esa parte.
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

/** Modelo Envio (baja lógica). El alta pasa por el procedimiento almacenado procedimiento_crear_envio. */
class Envio extends Modelo
{
    // CAMBIAR: columnas por las que se puede ordenar el listado:
    //          'nombre que manda la pantalla' => 'columna de la base'.
    private const ORDENABLES = [
        'nro_envio'   => 'v.nro_envio',
        'fecha_envio' => 'v.fecha_envio',
        'peso_kg'     => 'v.peso_kg',
    ];

    /**
     * Listado desde vista_envios_detalle: la vista arma los 8 JOIN (tipo, clientes, sucursales con
     * provincia, dirección). $f: nro_envio, fecha_desde, fecha_hasta, sucursal_orig, tipo, solo_pendientes.
     */
    public function listar(array $f, array $p, bool $inactivos): array
    {
        $cond = [];
        $par  = [];
        if (!$inactivos)                { $cond[] = 'v.activo = 1'; }
        if (!empty($f['nro_envio']))     { $cond[] = 'v.nro_envio = :nro';           $par[':nro'] = $f['nro_envio']; }
        if (!empty($f['fecha_desde']))   { $cond[] = 'v.fecha_envio >= :fd';         $par[':fd'] = $f['fecha_desde']; }
        if (!empty($f['fecha_hasta']))   { $cond[] = 'v.fecha_envio <= :fh';         $par[':fh'] = $f['fecha_hasta']; }
        if (!empty($f['sucursal_orig'])) { $cond[] = 'v.sucursal_orig_cod = :so';    $par[':so'] = $f['sucursal_orig']; }
        if (!empty($f['tipo']))          { $cond[] = 'v.tipo_cont_cod = :tipo';      $par[':tipo'] = $f['tipo']; }
        if (!empty($f['solo_pendientes'])) { $cond[] = 'funcion_envio_terminado(v.nro_envio) = 0'; }

        // Filtros y orden sobre la tabla envio (alias v); las 50 filas de la página, desde la vista.
        $res = $this->paginarPorClave('envio v', 'nro_envio', 'vista_envios_detalle',
                                      $cond, $par, self::ORDENABLES, $p['orden'], $p['dir'], $p['pagina']);

        // El último estado se calcula solo para las filas de esta página (ver el script 014).
        $res['datos'] = $this->conNombreDeEstado($res['datos']);
        return $res;
    }

    /**
     * Alta mediante el procedimiento almacenado, dentro de una transacción: si algo falla se hace
     * ROLLBACK y no queda un envío a medias. El trigger trigger_after_insert_envio registra el estado
     * inicial (RECIBIDO) en historial_estado. Devuelve el número de envío creado.
     * Si ya hay una transacción abierta (el asistente crea envío + viaje + asignación juntos), se
     * suma a esa y el COMMIT o ROLLBACK lo decide quien la abrió.
     */
    public function crear(array $e): int
    {
        if ($this->pdo->inTransaction()) {
            return $this->llamarProcedimiento($e);
        }
        $this->pdo->beginTransaction();
        try {
            $nro = $this->llamarProcedimiento($e);
            $this->pdo->commit();
            return $nro;
        } catch (Throwable $t) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $t;
        }
    }

    private function llamarProcedimiento(array $e): int
    {
        $st = $this->pdo->prepare('CALL procedimiento_crear_envio(:fecha, :peso, :tipo, :rem, :dest, :so, :sd, :dir)');
        $st->execute([
            ':fecha' => $e['fecha_envio'], ':peso' => $e['peso_kg'], ':tipo' => $e['tipo_cont_cod'],
            ':rem'   => $e['remitente_dni'], ':dest' => $e['destinatario_dni'],
            ':so'    => $e['sucursal_orig_cod'], ':sd' => $e['sucursal_dest_cod'], ':dir' => $e['direccion_entrega_id'],
        ]);
        $nro = (int)$st->fetchColumn();
        $st->closeCursor();
        return $nro;
    }

    /** Si cambia la sucursal de destino, trigger_after_update_envio registra REDIRIGIDO en el historial. */
    public function actualizar(int $nro, array $e): bool
    {
        return $this->ejecutar(
            'UPDATE envio SET fecha_envio = :fecha, peso_kg = :peso, tipo_cont_cod = :tipo,
                              remitente_dni = :rem, destinatario_dni = :dest,
                              sucursal_orig_cod = :so, sucursal_dest_cod = :sd, direccion_entrega_id = :dir
             WHERE nro_envio = :nro AND activo = 1',
            [':fecha' => $e['fecha_envio'], ':peso' => $e['peso_kg'], ':tipo' => $e['tipo_cont_cod'],
             ':rem' => $e['remitente_dni'], ':dest' => $e['destinatario_dni'],
             ':so' => $e['sucursal_orig_cod'], ':sd' => $e['sucursal_dest_cod'],
             ':dir' => $e['direccion_entrega_id'], ':nro' => $nro]
        ) > 0;
    }

    /** Baja lógica: se conserva con su historial de estados y sus viajes. */
    public function cambiarActivo(int $nro, bool $activo): bool
    {
        return $this->ejecutar('UPDATE envio SET activo = :a WHERE nro_envio = :nro',
                               [':a' => (int)$activo, ':nro' => $nro]) > 0;
    }
}
