// ==============================================================================================
// QUÉ ES:  el comportamiento de la pantalla "Clientes" (el diseño está en clientes.php).
//          Es una CONFIGURACIÓN: dice qué columnas mostrar, qué campos tiene el formulario
//          y qué filtros hay. Listar, guardar, editar y borrar lo hace paginas/crud.js,
//          igual para todas las pantallas.
// PARA CAMBIAR: buscá "CAMBIAR:" en este archivo.
//   - Los textos de los mensajes que vienen del servidor no están acá: los manda la API
//     (api/) o la base (triggers y procedimientos).
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
// Pantalla de clientes: solo describe qué mostrar; el comportamiento está en ../crud.js
cargarCatalogo('input-localidad', 'localidades');

pantallaCrud({
    api: '../../api/clientes.php',
    entidad: 'clientes',
    clave: 'cliente_dni',
    nombre: 'el cliente',
    tabla: 'tabla-clientes',
    paginacion: 'paginacion',
    // CAMBIAR: columnas de la tabla, en orden. Cada una tiene su título <th> en la .php,
    //          en la misma posición. esc() muestra el dato tal cual (protege contra código malicioso).
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
    // CAMBIAR: campos del formulario: 'id del <input> en la .php': 'nombre del dato en la API'.
    campos: {
        'input-dni': 'cliente_dni',
        'input-nombre': 'nombre',
        'input-apellido': 'apellido',
        'input-mail': 'mail',
        'input-telefono': 'telefono',
        'input-localidad': 'localidad_cod',
    },
    // CAMBIAR: filtros de arriba de la tabla: 'id del <input>': 'parámetro que recibe la API'.
    filtros: {
        'filtro-dni': 'dni',
        'filtro-nombre': 'nombre',
        'filtro-apellido': 'apellido',
        'filtro-mostrar-inactivos': 'mostrar_inactivos',
    },
    // CAMBIAR: orden inicial del listado (columna y ASC = ascendente / DESC = descendente).
    orden: { col: 'cliente_dni', dir: 'ASC' },
    encabezados: { 'th-dni': 'cliente_dni', 'th-nombre': 'nombre', 'th-apellido': 'apellido', 'th-localidad': 'localidad_nombre' },
    // CAMBIAR: cómo se borra: 'logica' = se da de baja sin borrar (se puede reactivar),
    //          'fisica' = se borra de verdad, null = sin botón de borrar.
    baja: 'logica',
});
