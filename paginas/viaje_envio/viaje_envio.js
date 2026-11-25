// Pantalla de la tabla puente viaje_envio: asignar y quitar envíos de un viaje.
// La clave es compuesta (viaje + envío), así que la fila se identifica con "viaje-envío".
const API_ASIGNACIONES = '../../api/viaje_envio.php';

pantallaCrud({
    api: API_ASIGNACIONES,
    entidad: 'viaje_envio',
    clave: 'asignacion',
    nombre: 'la asignación',
    tabla: 'tabla-asignaciones',
    paginacion: 'paginacion',
    columnas: [
        f => esc(f.viaje_id),
        f => esc(f.fecha_salida),
        f => (f.fecha_llegada ? esc(f.fecha_llegada) : '<em>En curso</em>'),
        f => esc(f.nro_envio),
        f => (f.tramo == 1 ? '1' : `${esc(f.tramo)} <small>(trasbordo)</small>`),
        f => esc(f.fecha_envio),
        f => `${esc(f.viaje_origen)} → ${esc(f.viaje_destino)}<br><small>Envío: ${esc(f.sucursal_orig_nombre)} → ${esc(f.sucursal_dest_nombre)}</small>`,
    ],
    form: 'form-asignacion',
    titulo: 'titulo-form',
    tituloAlta: 'Asignar un envío a un viaje',
    campos: { 'input-viaje': 'viaje_id', 'input-envio': 'nro_envio' },
    filtros: { 'filtro-viaje': 'viaje_id', 'filtro-envio': 'nro_envio' },
    orden: { col: 'viaje_id', dir: 'DESC' },
    encabezados: { 'th-viaje': 'viaje_id', 'th-envio': 'nro_envio' },
    editable: false,
    baja: null,
    accionesExtra: f => `<button data-accion="quitar" data-clave="${esc(f.asignacion)}">Quitar del viaje</button>`,
    acciones: {
        quitar: async (f, pantalla) => {
            if (!confirm(`¿Quitar el envío ${f.nro_envio} del viaje ${f.viaje_id}?`)) return;
            try {
                alert((await api(API_ASIGNACIONES, 'DELETE', { viaje_id: f.viaje_id, nro_envio: f.nro_envio })).mensaje);
                pantalla.listar();
            } catch (err) {
                mostrarError(err);
            }
        },
    },
});
