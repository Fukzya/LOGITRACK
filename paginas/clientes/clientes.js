// Pantalla de clientes: solo describe qué mostrar; el comportamiento está en ../crud.js
cargarCatalogo('input-localidad', 'localidades');

pantallaCrud({
    api: '../../api/clientes.php',
    entidad: 'clientes',
    clave: 'cliente_dni',
    nombre: 'el cliente',
    tabla: 'tabla-clientes',
    paginacion: 'paginacion',
    columnas: [
        f => esc(f.cliente_dni),
        f => esc(f.nombre),
        f => esc(f.apellido),
        f => esc(f.mail),
        f => esc(f.telefono),
        f => esc(f.localidad_nombre),
        celdaEstado,
    ],
    form: 'form-cliente',
    titulo: 'titulo-form',
    tituloAlta: 'Nuevo cliente',
    tituloEdicion: f => `Editando cliente ${f.cliente_dni}`,
    campos: {
        'input-dni': 'cliente_dni',
        'input-nombre': 'nombre',
        'input-apellido': 'apellido',
        'input-mail': 'mail',
        'input-telefono': 'telefono',
        'input-localidad': 'localidad_cod',
    },
    filtros: {
        'filtro-dni': 'dni',
        'filtro-nombre': 'nombre',
        'filtro-apellido': 'apellido',
        'filtro-mostrar-inactivos': 'mostrar_inactivos',
    },
    orden: { col: 'cliente_dni', dir: 'ASC' },
    encabezados: { 'th-dni': 'cliente_dni', 'th-nombre': 'nombre', 'th-apellido': 'apellido', 'th-localidad': 'localidad_nombre' },
    baja: 'logica',
});
