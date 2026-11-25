// ==============================================================================================
// QUÉ ES:  el comportamiento de la pantalla "Localidades" (el diseño está en localidades.php).
//          Es una CONFIGURACIÓN: dice qué columnas mostrar, qué campos tiene el formulario
//          y qué filtros hay. Listar, guardar, editar y borrar lo hace paginas/crud.js,
//          igual para todas las pantallas.
// PARA CAMBIAR: buscá "CAMBIAR:" en este archivo.
//   - Los textos de los mensajes que vienen del servidor no están acá: los manda la API
//     (api/) o la base (triggers y procedimientos).
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
// Pantalla de localidades (catálogo con baja física).
cargarCatalogo('input-provincia', 'provincias');
cargarCatalogo('filtro-provincia', 'provincias');

pantallaCrud({
    api: '../../api/localidades.php',
    entidad: 'localidades',
    clave: 'localidad_cod',
    nombre: 'la localidad',
    tabla: 'tabla-localidades',
    // CAMBIAR: columnas de la tabla, en orden. Cada una tiene su título <th> en la .php,
    //          en la misma posición. esc() muestra el dato tal cual (protege contra código malicioso).
    columnas: [f => esc(f.localidad_cod), f => esc(f.localidad_nombre), f => esc(f.provincia_nombre)],
    form: 'form-localidad',
    titulo: 'titulo-form',
    tituloAlta: 'Nueva localidad',
    tituloEdicion: f => `Editando ${f.localidad_nombre}`,
    // CAMBIAR: campos del formulario: 'id del <input> en la .php': 'nombre del dato en la API'.
    campos: { 'input-nombre': 'localidad_nombre', 'input-provincia': 'provincia_cod' },
    // CAMBIAR: filtros de arriba de la tabla: 'id del <input>': 'parámetro que recibe la API'.
    filtros: { 'filtro-provincia': 'provincia_cod' },
    // CAMBIAR: orden inicial del listado (columna y ASC = ascendente / DESC = descendente).
    orden: { col: 'provincia_nombre', dir: 'ASC' },
    encabezados: { 'th-cod': 'localidad_cod', 'th-nombre': 'localidad_nombre', 'th-provincia': 'provincia_nombre' },
    // CAMBIAR: cómo se borra: 'logica' = se da de baja sin borrar (se puede reactivar),
    //          'fisica' = se borra de verdad, null = sin botón de borrar.
    baja: 'fisica',
});
