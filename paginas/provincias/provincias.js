// ==============================================================================================
// QUÉ ES:  el comportamiento de la pantalla "Provincias" (el diseño está en provincias.php).
//          Es una CONFIGURACIÓN: dice qué columnas mostrar, qué campos tiene el formulario
//          y qué filtros hay. Listar, guardar, editar y borrar lo hace paginas/crud.js,
//          igual para todas las pantallas.
// PARA CAMBIAR: buscá "CAMBIAR:" en este archivo.
//   - Los textos de los mensajes que vienen del servidor no están acá: los manda la API
//     (api/) o la base (triggers y procedimientos).
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
// Pantalla de provincias (catálogo con baja física).
pantallaCrud({
    api: '../../api/provincias.php',
    entidad: 'provincias',
    clave: 'provincia_cod',
    nombre: 'la provincia',
    tabla: 'tabla-provincias',
    // CAMBIAR: columnas de la tabla, en orden. Cada una tiene su título <th> en la .php,
    //          en la misma posición. esc() muestra el dato tal cual (protege contra código malicioso).
    columnas: [f => esc(f.provincia_cod), f => esc(f.provincia_nombre)],
    form: 'form-provincia',
    titulo: 'titulo-form',
    tituloAlta: 'Nueva provincia',
    tituloEdicion: f => `Editando ${f.provincia_nombre}`,
    // CAMBIAR: campos del formulario: 'id del <input> en la .php': 'nombre del dato en la API'.
    campos: { 'input-nombre': 'provincia_nombre' },
    // CAMBIAR: orden inicial del listado (columna y ASC = ascendente / DESC = descendente).
    orden: { col: 'provincia_nombre', dir: 'ASC' },
    encabezados: { 'th-cod': 'provincia_cod', 'th-nombre': 'provincia_nombre' },
    // CAMBIAR: cómo se borra: 'logica' = se da de baja sin borrar (se puede reactivar),
    //          'fisica' = se borra de verdad, null = sin botón de borrar.
    baja: 'fisica',
});
