// Pantalla de envíos (baja lógica).
cargarCatalogo('input-tipo', 'tipos_contenido');
cargarCatalogo('filtro-tipo', 'tipos_contenido');
cargarCatalogo('input-suc-orig', 'sucursales');
cargarCatalogo('input-suc-dest', 'sucursales');
cargarCatalogo('filtro-sucursal-orig', 'sucursales');

cargarCatalogo('estado-nuevo', 'estados');
cargarCatalogo('estado-sucursal', 'sucursales');

const pantalla = pantallaCrud({
    api: '../../api/envios.php',
    clave: 'nro_envio',
    nombre: 'el envío',
    tabla: 'tabla-envios',
    paginacion: 'paginacion',
    columnas: [
        f => esc(f.nro_envio),
        f => esc(f.fecha_envio),
        f => esc(f.peso_kg),
        f => esc(f.tipo_cont_nombre),
        f => `${esc(f.remitente_nombre)}<br><small>${esc(f.remitente_dni)}</small>`,
        f => `${esc(f.destinatario_nombre)}<br><small>${esc(f.destinatario_dni)}</small>`,
        f => esc(f.sucursal_orig_nombre),
        f => esc(f.sucursal_dest_nombre),
        f => `${esc(f.direccion_entrega)}<br><small>ID ${esc(f.direccion_entrega_id)}</small>`,
        f => esc(f.ultimo_estado ?? 'Sin estados'),
        celdaEstado,
    ],
    // Abre el historial completo de ese envío (historial_estado.php lee el filtro desde la URL).
    // "Cambiar estado" solo si está activo y no terminó (entregado o devuelto).
    accionesExtra: f => (f.activo == 1 && f.terminado != 1 ? `<button data-accion="estado" data-clave="${esc(f.nro_envio)}">Cambiar estado</button> ` : '')
        + `<button data-accion="historial" data-clave="${esc(f.nro_envio)}">Ver historial</button>`,
    acciones: {
        historial: f => { location.href = `../historial_estado/historial_estado.php?nro_envio=${encodeURIComponent(f.nro_envio)}`; },
        estado: f => abrirCambioEstado(f),
    },
    form: 'form-envio',
    titulo: 'titulo-form',
    tituloAlta: 'Nuevo envío',
    tituloEdicion: f => `Editando envío ${f.nro_envio}`,
    campos: {
        'input-fecha': 'fecha_envio',
        'input-peso': 'peso_kg',
        'input-tipo': 'tipo_cont_cod',
        'input-remitente': 'remitente_dni',
        'input-destinatario': 'destinatario_dni',
        'input-suc-orig': 'sucursal_orig_cod',
        'input-suc-dest': 'sucursal_dest_cod',
        'input-direccion': 'direccion_entrega_id',
    },
    filtros: {
        'filtro-fecha-desde': 'fecha_desde',
        'filtro-fecha-hasta': 'fecha_hasta',
        'filtro-sucursal-orig': 'sucursal_orig',
        'filtro-tipo': 'tipo_cont',
        'filtro-mostrar-inactivos': 'mostrar_inactivos',
    },
    orden: { col: 'nro_envio', dir: 'DESC' },
    encabezados: { 'th-nro': 'nro_envio', 'th-fecha': 'fecha_envio', 'th-peso': 'peso_kg' },
    baja: 'logica',
});

// ------------------------------------------------------------ cambiar estado
// Registra una fila nueva en el historial con procedimiento_registrar_estado_envio. Las reglas de
// fechas (no futura, no antes del último estado, nada después de uno final) las controla la base.
const dlgEstado = document.getElementById('dialogo-estado');
let envioEstado = null;

async function abrirCambioEstado(f) {
    envioEstado = f;
    document.getElementById('titulo-estado').textContent = `Cambiar estado del envío ${f.nro_envio}`;
    document.getElementById('texto-estado').textContent = `Estado actual: ${f.ultimo_estado ?? 'sin estados'}.`;
    document.getElementById('form-estado').reset();
    const fecha = document.getElementById('estado-fecha');
    fecha.min = '';
    limitarHastaAhora(fecha);
    fecha.value = fecha.max;                                   // por defecto: ahora
    // Sucursal sugerida: la del último estado (si no, la de origen).
    let sucursal = f.sucursal_orig_cod;
    try {
        const h = await api(`../../api/historial_estado.php?nro_envio=${encodeURIComponent(f.nro_envio)}&order_by=fecha_hora&order_dir=DESC`);
        if (h.datos.length) {
            sucursal = h.datos[0].sucursal_cod;
            fecha.min = String(h.datos[0].fecha_hora).replace(' ', 'T').slice(0, 16);
        }
    } catch { /* sin historial: queda la de origen */ }
    document.getElementById('estado-sucursal').value = sucursal;
    dlgEstado.showModal();
}

document.getElementById('btn-cancelar-estado').addEventListener('click', () => dlgEstado.close());
document.getElementById('form-estado').addEventListener('submit', async e => {
    e.preventDefault();
    try {
        const r = await api('../../api/historial_estado.php', 'POST', {
            nro_envio: envioEstado.nro_envio,
            estado: document.getElementById('estado-nuevo').value,
            sucursal_cod: document.getElementById('estado-sucursal').value,
            fecha_hora: document.getElementById('estado-fecha').value,
            observacion: document.getElementById('estado-observacion').value,
        });
        dlgEstado.close();
        alert(r.mensaje);
        pantalla.listar();
    } catch (err) {
        mostrarError(err);
    }
});
