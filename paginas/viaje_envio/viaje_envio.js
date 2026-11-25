// ==============================================================================================
// QUÉ ES:  el comportamiento de la pantalla "Viaje-Envío" (el diseño está en viaje_envio.php).
//          Es una CONFIGURACIÓN: dice qué columnas mostrar, qué campos tiene el formulario
//          y qué filtros hay. Listar, guardar, editar y borrar lo hace paginas/crud.js,
//          igual para todas las pantallas.
// PARA CAMBIAR: buscá "CAMBIAR:" en este archivo.
//   - Los textos de los mensajes que vienen del servidor no están acá: los manda la API
//     (api/) o la base (triggers y procedimientos).
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
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
    // CAMBIAR: columnas de la tabla, en orden. Cada una tiene su título <th> en la .php,
    //          en la misma posición. esc() muestra el dato tal cual (protege contra código malicioso).
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
    // CAMBIAR: campos del formulario: 'id del <input> en la .php': 'nombre del dato en la API'.
    campos: { 'input-viaje': 'viaje_id', 'input-envio': 'nro_envio' },
    // CAMBIAR: filtros de arriba de la tabla: 'id del <input>': 'parámetro que recibe la API'.
    filtros: { 'filtro-viaje': 'viaje_id', 'filtro-envio': 'nro_envio' },
    // CAMBIAR: orden inicial del listado (columna y ASC = ascendente / DESC = descendente).
    orden: { col: 'viaje_id', dir: 'DESC' },
    encabezados: { 'th-viaje': 'viaje_id', 'th-envio': 'nro_envio' },
    editable: false,
    // CAMBIAR: cómo se borra: 'logica' = se da de baja sin borrar (se puede reactivar),
    //          'fisica' = se borra de verdad, null = sin botón de borrar.
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
