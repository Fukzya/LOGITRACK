// Pantalla de choferes (baja lógica).
cargarCatalogo('input-licencia', 'licencias');
cargarCatalogo('filtro-licencia', 'licencias');

pantallaCrud({
    api: '../../api/choferes.php',
    clave: 'chofer_legajo',
    nombre: 'el chofer',
    tabla: 'tabla-choferes',
    paginacion: 'paginacion',
    columnas: [
        f => esc(f.chofer_legajo),
        f => esc(f.nombre),
        f => esc(f.apellido),
        f => `${esc(f.chofer_tipo_licencia)} - ${esc(f.licencia_descripcion)}`,
        celdaEstado,
    ],
    form: 'form-chofer',
    titulo: 'titulo-form',
    tituloAlta: 'Nuevo chofer',
    tituloEdicion: f => `Editando chofer ${f.chofer_legajo}`,
    campos: { 'input-nombre': 'nombre', 'input-apellido': 'apellido', 'input-licencia': 'chofer_tipo_licencia' },
    filtros: {
        'filtro-nombre': 'nombre',
        'filtro-apellido': 'apellido',
        'filtro-licencia': 'tipo_licencia',
        'filtro-mostrar-inactivos': 'mostrar_inactivos',
    },
    orden: { col: 'chofer_legajo', dir: 'ASC' },
    encabezados: { 'th-legajo': 'chofer_legajo', 'th-nombre': 'nombre', 'th-apellido': 'apellido', 'th-licencia': 'chofer_tipo_licencia' },
    baja: 'logica',
});
