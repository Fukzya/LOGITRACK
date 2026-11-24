// Pantalla de sucursales (baja lógica).
cargarCatalogo('input-localidad', 'localidades');

pantallaCrud({
    api: '../../api/sucursales.php',
    clave: 'sucursal_cod',
    nombre: 'la sucursal',
    tabla: 'tabla-sucursales',
    paginacion: 'paginacion',
    columnas: [
        f => esc(f.sucursal_cod),
        f => esc(f.sucursal_nombre),
        f => esc(f.localidad_nombre),
        f => esc(f.provincia_nombre),
        f => esc(f.direccion),
        celdaEstado,
    ],
    form: 'form-sucursal',
    titulo: 'titulo-form',
    tituloAlta: 'Nueva sucursal',
    tituloEdicion: f => `Editando sucursal ${f.sucursal_cod}`,
    campos: {
        'input-cod': 'sucursal_cod',
        'input-nombre': 'sucursal_nombre',
        'input-localidad': 'sucursal_localidad_cod',
        'input-direccion': 'direccion',
    },
    filtros: { 'filtro-nombre': 'nombre', 'filtro-mostrar-inactivos': 'mostrar_inactivos' },
    orden: { col: 'sucursal_cod', dir: 'ASC' },
    encabezados: { 'th-cod': 'sucursal_cod', 'th-nombre': 'sucursal_nombre', 'th-localidad': 'localidad_nombre', 'th-provincia': 'provincia_nombre' },
    baja: 'logica',
});
