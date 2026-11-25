// Pantalla de localidades (catálogo con baja física).
cargarCatalogo('input-provincia', 'provincias');
cargarCatalogo('filtro-provincia', 'provincias');

pantallaCrud({
    api: '../../api/localidades.php',
    entidad: 'localidades',
    clave: 'localidad_cod',
    nombre: 'la localidad',
    tabla: 'tabla-localidades',
    columnas: [f => esc(f.localidad_cod), f => esc(f.localidad_nombre), f => esc(f.provincia_nombre)],
    form: 'form-localidad',
    titulo: 'titulo-form',
    tituloAlta: 'Nueva localidad',
    tituloEdicion: f => `Editando ${f.localidad_nombre}`,
    campos: { 'input-nombre': 'localidad_nombre', 'input-provincia': 'provincia_cod' },
    filtros: { 'filtro-provincia': 'provincia_cod' },
    orden: { col: 'provincia_nombre', dir: 'ASC' },
    encabezados: { 'th-cod': 'localidad_cod', 'th-nombre': 'localidad_nombre', 'th-provincia': 'provincia_nombre' },
    baja: 'fisica',
});
