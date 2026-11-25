// Pantalla de tipos de vehículo (catálogo con baja física).
pantallaCrud({
    api: '../../api/tipo_vehiculo.php',
    entidad: 'tipo_vehiculo',
    clave: 'tipo_veh_cod',
    nombre: 'el tipo',
    tabla: 'tabla-tipos',
    columnas: [
        f => esc(f.tipo_veh_cod),
        f => esc(f.tipo_veh_nombre),
        f => esc(f.tipo_veh_capacidad_kg),
        f => (f.tipo_veh_requiere_lic_especial == 1 ? 'Sí' : 'No'),
    ],
    form: 'form-tipo',
    titulo: 'titulo-form',
    tituloAlta: 'Nuevo tipo de vehículo',
    tituloEdicion: f => `Editando ${f.tipo_veh_cod}`,
    campos: {
        'input-cod': 'tipo_veh_cod',
        'input-nombre': 'tipo_veh_nombre',
        'input-capacidad': 'tipo_veh_capacidad_kg',
        'input-licencia': 'tipo_veh_requiere_lic_especial',
    },
    orden: { col: 'tipo_veh_nombre', dir: 'ASC' },
    encabezados: { 'th-cod': 'tipo_veh_cod', 'th-nombre': 'tipo_veh_nombre', 'th-capacidad': 'tipo_veh_capacidad_kg' },
    baja: 'fisica',
});
