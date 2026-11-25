// Pantalla de tipos de contenido (catálogo con baja física).
pantallaCrud({
    api: '../../api/tipo_contenido.php',
    entidad: 'tipo_contenido',
    clave: 'tipo_cont_cod',
    nombre: 'el tipo',
    tabla: 'tabla-tipos',
    columnas: [
        f => esc(f.tipo_cont_cod),
        f => esc(f.tipo_cont_nombre),
        f => (f.tipo_cont_requiere_seguro == 1 ? 'Sí' : 'No'),
    ],
    form: 'form-tipo',
    titulo: 'titulo-form',
    tituloAlta: 'Nuevo tipo de contenido',
    tituloEdicion: f => `Editando ${f.tipo_cont_cod}`,
    campos: { 'input-cod': 'tipo_cont_cod', 'input-nombre': 'tipo_cont_nombre', 'input-seguro': 'tipo_cont_requiere_seguro' },
    orden: { col: 'tipo_cont_nombre', dir: 'ASC' },
    encabezados: { 'th-cod': 'tipo_cont_cod', 'th-nombre': 'tipo_cont_nombre' },
    baja: 'fisica',
});
