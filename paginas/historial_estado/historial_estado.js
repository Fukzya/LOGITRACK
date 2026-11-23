// Pantalla del historial de estados de los envíos (baja física).
cargarCatalogo('input-estado', 'estados');
cargarCatalogo('filtro-estado', 'estados');
cargarCatalogo('input-sucursal', 'sucursales');
cargarCatalogo('filtro-sucursal', 'sucursales');

pantallaCrud({
    api: '../../api/historial_estado.php',
    clave: 'id_historial',
    nombre: 'el registro',
    tabla: 'tabla-historial',
    paginacion: 'paginacion',
    columnas: [
        f => esc(f.id_historial),
        f => esc(f.nro_envio),
        f => `${esc(f.sucursal_cod)} - ${esc(f.sucursal_nombre)}`,
        f => esc(f.estado_nombre),
        f => esc(f.fecha_hora),
        f => esc(f.observacion),
    ],
    form: 'form-historial',
    titulo: 'titulo-form',
    tituloAlta: 'Registrar un estado',
    tituloEdicion: f => `Editando registro ${f.id_historial}`,
    campos: {
        'input-envio': 'nro_envio',
        'input-sucursal': 'sucursal_cod',
        'input-estado': 'estado',
        'input-fecha': 'fecha_hora',
        'input-observacion': 'observacion',
    },
    filtros: {
        'filtro-envio': 'nro_envio',
        'filtro-estado': 'estado',
        'filtro-sucursal': 'sucursal_cod',
        'filtro-fecha-desde': 'fecha_desde',
        'filtro-fecha-hasta': 'fecha_hasta',
    },
    orden: { col: 'id_historial', dir: 'DESC' },
    encabezados: { 'th-id': 'id_historial', 'th-envio': 'nro_envio', 'th-fecha': 'fecha_hora' },
    baja: 'fisica',
});
