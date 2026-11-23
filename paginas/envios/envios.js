// Pantalla de envíos (baja lógica).
cargarCatalogo('input-tipo', 'tipos_contenido');
cargarCatalogo('filtro-tipo', 'tipos_contenido');
cargarCatalogo('input-suc-orig', 'sucursales');
cargarCatalogo('input-suc-dest', 'sucursales');
cargarCatalogo('filtro-sucursal-orig', 'sucursales');

pantallaCrud({
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
        celdaEstado,
    ],
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
