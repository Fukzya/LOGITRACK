// Pantalla de vehículos (baja lógica).
cargarCatalogo('input-tipo', 'tipos_vehiculo');
cargarCatalogo('filtro-tipo', 'tipos_vehiculo');

pantallaCrud({
    api: '../../api/vehiculos.php',
    clave: 'vehiculo_patente',
    nombre: 'el vehículo',
    tabla: 'tabla-vehiculos',
    paginacion: 'paginacion',
    columnas: [
        f => esc(f.vehiculo_patente),
        f => esc(f.marca),
        f => esc(f.modelo),
        f => esc(f.anio),
        f => esc(f.tipo_veh_nombre),
        celdaEstado,
    ],
    form: 'form-vehiculo',
    titulo: 'titulo-form',
    tituloAlta: 'Nuevo vehículo',
    tituloEdicion: f => `Editando vehículo ${f.vehiculo_patente}`,
    campos: {
        'input-patente': 'vehiculo_patente',
        'input-marca': 'marca',
        'input-modelo': 'modelo',
        'input-anio': 'anio',
        'input-tipo': 'tipo_veh_cod',
    },
    filtros: {
        'filtro-patente': 'patente',
        'filtro-marca': 'marca',
        'filtro-tipo': 'tipo_veh_cod',
        'filtro-mostrar-inactivos': 'mostrar_inactivos',
    },
    orden: { col: 'vehiculo_patente', dir: 'ASC' },
    encabezados: {
        'th-patente': 'vehiculo_patente', 'th-marca': 'marca', 'th-modelo': 'modelo',
        'th-anio': 'anio', 'th-tipo': 'tipo_veh_nombre',
    },
    baja: 'logica',
});
