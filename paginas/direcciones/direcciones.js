// Pantalla de direcciones (baja lógica).
cargarCatalogo('input-localidad', 'localidades');
cargarCatalogo('filtro-localidad', 'localidades');

pantallaCrud({
    api: '../../api/direcciones.php',
    entidad: 'direcciones',
    clave: 'direccion_id',
    nombre: 'la dirección',
    tabla: 'tabla-direcciones',
    paginacion: 'paginacion',
    columnas: [
        f => esc(f.direccion_id),
        f => esc(f.calle_y_numero),
        f => esc(f.localidad_nombre),
        f => `${esc(f.cliente_dni)} - ${esc(f.cliente_nombre)}`,
        celdaEstado,
    ],
    form: 'form-direccion',
    titulo: 'titulo-form',
    tituloAlta: 'Nueva dirección',
    tituloEdicion: f => `Editando dirección ${f.direccion_id}`,
    campos: { 'input-calle': 'calle_y_numero', 'input-localidad': 'localidad_cod', 'input-dni': 'cliente_dni' },
    filtros: {
        'filtro-calle': 'calle',
        'filtro-dni': 'cliente_dni',
        'filtro-localidad': 'localidad_cod',
        'filtro-mostrar-inactivos': 'mostrar_inactivos',
    },
    orden: { col: 'direccion_id', dir: 'ASC' },
    encabezados: { 'th-id': 'direccion_id', 'th-calle': 'calle_y_numero', 'th-localidad': 'localidad_nombre', 'th-dni': 'cliente_dni' },
    baja: 'logica',
});
