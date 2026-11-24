<?php
require_once __DIR__ . '/Modelo.php';

/** Modelo Viaje (baja física: el trigger trigger_before_delete_viaje impide borrar uno con envíos). */
class Viaje extends Modelo
{
    private const ORDENABLES = [
        'viaje_id'      => 'v.viaje_id',
        'fecha_salida'  => 'v.fecha_salida',
        'fecha_llegada' => 'v.fecha_llegada',
    ];

    /** $f: fecha_desde, fecha_hasta, chofer, solo_activos (en curso: sin fecha de llegada). */
    public function listar(array $f, array $p): array
    {
        $cond = [];
        $par  = [];
        if (!empty($f['fecha_desde'])) { $cond[] = 'v.fecha_salida >= :fd';     $par[':fd'] = $f['fecha_desde'] . ' 00:00:00'; }
        if (!empty($f['fecha_hasta'])) { $cond[] = 'v.fecha_salida <= :fh';     $par[':fh'] = $f['fecha_hasta'] . ' 23:59:59'; }
        if (!empty($f['chofer']))      { $cond[] = 'v.chofer_legajo = :chofer'; $par[':chofer'] = $f['chofer']; }
        if (!empty($f['solo_activos'])) { $cond[] = 'v.fecha_llegada IS NULL'; }

        return $this->paginar(
            "v.viaje_id, v.fecha_salida, v.llegada_estimada, v.fecha_llegada,
             v.sucursal_origen_cod, so.sucursal_completa AS origen,
             v.sucursal_destino_cod, sd.sucursal_completa AS destino,
             v.vehiculo_patente, CONCAT(ve.marca, ' ', ve.modelo) AS vehiculo,
             v.chofer_legajo, CONCAT(c.nombre, ' ', c.apellido) AS chofer_nombre,
             (SELECT COUNT(*) FROM viaje_envio x WHERE x.viaje_id = v.viaje_id) AS cantidad_envios",
            'viaje v JOIN vehiculo ve ON ve.vehiculo_patente = v.vehiculo_patente
                     JOIN chofer c    ON c.chofer_legajo     = v.chofer_legajo
                     JOIN vista_sucursal_ubicacion so ON so.sucursal_cod = v.sucursal_origen_cod
                     JOIN vista_sucursal_ubicacion sd ON sd.sucursal_cod = v.sucursal_destino_cod',
            $cond, $par, self::ORDENABLES, $p['orden'], $p['dir'], $p['pagina']
        );
    }

    /**
     * Rutas para un envío, con el procedimiento almacenado procedimiento_buscar_rutas: viajes
     * directos y combinaciones de dos viajes con un trasbordo. Devuelve:
     *   opciones: [{tipo, prioridad, viaje1_id, viaje2_id, llegada_final}]
     *   viajes:   todos los viajes programados (vista_viajes_programados), para mostrar el detalle
     *             de cada opción y la lista de "otros viajes".
     */
    public function buscarRutas(string $origen, string $destino, float $peso): array
    {
        $st = $this->pdo->prepare('CALL procedimiento_buscar_rutas(:o, :d, :p)');
        $st->execute([':o' => $origen, ':d' => $destino, ':p' => $peso]);
        $opciones = $st->fetchAll(PDO::FETCH_ASSOC);
        $st->closeCursor();

        $viajes = $this->pdo->query('SELECT * FROM vista_viajes_programados ORDER BY fecha_salida')
                            ->fetchAll(PDO::FETCH_ASSOC);
        return ['opciones' => $opciones, 'viajes' => $viajes];
    }

    /** Vehículos y choferes activos sin otro viaje ese día (para crear un viaje desde el asistente). */
    public function recursosLibres(string $fecha): array
    {
        $vehiculos = $this->pdo->prepare(
            "SELECT ve.vehiculo_patente AS cod,
                    CONCAT(ve.vehiculo_patente, ' - ', ve.marca, ' ', ve.modelo, ' (', tv.tipo_veh_nombre, ', ',
                           FORMAT(tv.tipo_veh_capacidad_kg, 0, 'es_AR'), ' kg)') AS nombre,
                    tv.tipo_veh_capacidad_kg AS capacidad, tv.tipo_veh_requiere_lic_especial AS requiere_especial
               FROM vehiculo ve JOIN tipo_vehiculo tv ON tv.tipo_veh_cod = ve.tipo_veh_cod
              WHERE ve.activo = 1
                AND NOT EXISTS (SELECT 1 FROM viaje v WHERE v.vehiculo_patente = ve.vehiculo_patente
                                                        AND DATE(v.fecha_salida) = :f)
              ORDER BY tv.tipo_veh_capacidad_kg, ve.vehiculo_patente");
        $vehiculos->execute([':f' => $fecha]);

        $choferes = $this->pdo->prepare(
            "SELECT c.chofer_legajo AS cod,
                    CONCAT(c.apellido, ', ', c.nombre, ' (licencia ', c.chofer_tipo_licencia, ')') AS nombre,
                    c.chofer_tipo_licencia AS licencia
               FROM chofer c
              WHERE c.activo = 1
                AND NOT EXISTS (SELECT 1 FROM viaje v WHERE v.chofer_legajo = c.chofer_legajo
                                                        AND DATE(v.fecha_salida) = :f)
              ORDER BY c.apellido, c.nombre");
        $choferes->execute([':f' => $fecha]);

        return ['vehiculos' => $vehiculos->fetchAll(PDO::FETCH_ASSOC),
                'choferes'  => $choferes->fetchAll(PDO::FETCH_ASSOC)];
    }

    /**
     * Datos para validar un viaje nuevo: si el vehículo y el chofer existen y están activos, qué
     * licencia hace falta y si alguno ya tiene otro viaje ese día. null si alguno no existe.
     */
    public function datosRecursos(string $patente, int $legajo, string $fecha): ?array
    {
        return $this->fila(
            "SELECT ve.activo AS vehiculo_activo, tv.tipo_veh_requiere_lic_especial AS requiere_especial,
                    c.activo AS chofer_activo, c.chofer_tipo_licencia AS licencia,
                    EXISTS (SELECT 1 FROM viaje v WHERE v.vehiculo_patente = ve.vehiculo_patente AND DATE(v.fecha_salida) = :f1) AS vehiculo_ocupado,
                    EXISTS (SELECT 1 FROM viaje v WHERE v.chofer_legajo    = c.chofer_legajo    AND DATE(v.fecha_salida) = :f2) AS chofer_ocupado
               FROM vehiculo ve
               JOIN tipo_vehiculo tv ON tv.tipo_veh_cod = ve.tipo_veh_cod
               JOIN chofer c ON c.chofer_legajo = :legajo
              WHERE ve.vehiculo_patente = :patente",
            [':f1' => $fecha, ':f2' => $fecha, ':legajo' => $legajo, ':patente' => $patente]);
    }

    /**
     * Bloquea la fila del viaje hasta el fin de la transacción (SELECT ... FOR UPDATE) y devuelve su
     * capacidad y su carga actual. Si dos operadores asignan envíos al mismo viaje a la vez, el segundo
     * espera a que termine el primero y ve la carga ya actualizada: no se puede pasar de la capacidad.
     */
    public function bloquearParaAsignar(int $id): ?array
    {
        $viaje = $this->fila(
            'SELECT v.viaje_id, v.fecha_salida, v.fecha_llegada, tv.tipo_veh_capacidad_kg AS capacidad
               FROM viaje v
               JOIN vehiculo ve      ON ve.vehiculo_patente = v.vehiculo_patente
               JOIN tipo_vehiculo tv ON tv.tipo_veh_cod     = ve.tipo_veh_cod
              WHERE v.viaje_id = :id
                FOR UPDATE', [':id' => $id]);
        if ($viaje === null) return null;
        $viaje['carga'] = (float)$this->fila(
            'SELECT COALESCE(SUM(e.peso_kg), 0) AS carga
               FROM viaje_envio x JOIN envio e ON e.nro_envio = x.nro_envio
              WHERE x.viaje_id = :id', [':id' => $id])['carga'];
        return $viaje;
    }

    public function crear(array $v): int
    {
        $this->ejecutar('INSERT INTO viaje (fecha_salida, fecha_llegada, sucursal_origen_cod, sucursal_destino_cod,
                                            vehiculo_patente, chofer_legajo)
                         VALUES (:s, :l, :o, :d, :p, :c)',
                        [':s' => $v['fecha_salida'], ':l' => $v['fecha_llegada'],
                         ':o' => $v['sucursal_origen_cod'], ':d' => $v['sucursal_destino_cod'],
                         ':p' => $v['vehiculo_patente'], ':c' => $v['chofer_legajo']]);
        return (int)$this->pdo->lastInsertId();
    }

    /** trigger_before_update_viaje rechaza una llegada anterior a la salida. */
    public function actualizar(int $id, array $v): bool
    {
        return $this->ejecutar('UPDATE viaje SET fecha_salida = :s, fecha_llegada = :l,
                                       sucursal_origen_cod = :o, sucursal_destino_cod = :d,
                                       vehiculo_patente = :p, chofer_legajo = :c
                                WHERE viaje_id = :id',
                               [':s' => $v['fecha_salida'], ':l' => $v['fecha_llegada'],
                                ':o' => $v['sucursal_origen_cod'], ':d' => $v['sucursal_destino_cod'],
                                ':p' => $v['vehiculo_patente'], ':c' => $v['chofer_legajo'], ':id' => $id]) > 0;
    }

    /** Baja física. La impiden el trigger (si tiene envíos) o la FK de incidente (si tiene incidentes). */
    public function eliminar(int $id): bool
    {
        return $this->ejecutar('DELETE FROM viaje WHERE viaje_id = :id', [':id' => $id]) > 0;
    }
}
