// Pantalla de provincias (catálogo con baja física).
pantallaCrud({
    api: '../../api/provincias.php',
    entidad: 'provincias',
    clave: 'provincia_cod',
    nombre: 'la provincia',
    tabla: 'tabla-provincias',
    columnas: [f => esc(f.provincia_cod), f => esc(f.provincia_nombre)],
    form: 'form-provincia',
    titulo: 'titulo-form',
    tituloAlta: 'Nueva provincia',
    tituloEdicion: f => `Editando ${f.provincia_nombre}`,
    campos: { 'input-nombre': 'provincia_nombre' },
    orden: { col: 'provincia_nombre', dir: 'ASC' },
    encabezados: { 'th-cod': 'provincia_cod', 'th-nombre': 'provincia_nombre' },
    baja: 'fisica',
});
