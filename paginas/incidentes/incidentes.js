// Pantalla de incidentes de los viajes (baja física).
cargarCatalogo('input-tipo', 'incidentes');
cargarCatalogo('filtro-tipo', 'incidentes');

pantallaCrud({
    api: '../../api/incidentes.php',
    entidad: 'incidentes',
    clave: 'incidente_id',
    nombre: 'el incidente',
    tabla: 'tabla-incidentes',
    paginacion: 'paginacion',
    columnas: [
        f => esc(f.incidente_id),
        f => esc(f.viaje_id),
        f => esc(f.fecha_hora),
        f => esc(f.tipo_incidente_nombre),
        f => esc(f.descripcion),
    ],
    form: 'form-incidente',
    titulo: 'titulo-form',
    tituloAlta: 'Registrar un incidente',
    tituloEdicion: f => `Editando incidente ${f.incidente_id}`,
    campos: {
        'input-viaje': 'viaje_id',
        'input-fecha': 'fecha_hora',
        'input-tipo': 'tipo_incidente',
        'input-descripcion': 'descripcion',
    },
    filtros: {
        'filtro-viaje': 'viaje_id',
        'filtro-tipo': 'tipo',
        'filtro-fecha-desde': 'fecha_desde',
        'filtro-fecha-hasta': 'fecha_hasta',
    },
    orden: { col: 'fecha_hora', dir: 'DESC' },
    encabezados: { 'th-id': 'incidente_id', 'th-viaje': 'viaje_id', 'th-fecha': 'fecha_hora', 'th-tipo': 'tipo_incidente' },
    baja: 'fisica',
});
