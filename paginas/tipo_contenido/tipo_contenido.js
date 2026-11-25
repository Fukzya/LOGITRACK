// ==============================================================================================
// QUÉ ES:  el comportamiento de la pantalla "Tipos de contenido" (el diseño está en tipo_contenido.php).
//          Es una CONFIGURACIÓN: dice qué columnas mostrar, qué campos tiene el formulario
//          y qué filtros hay. Listar, guardar, editar y borrar lo hace paginas/crud.js,
//          igual para todas las pantallas.
// PARA CAMBIAR: buscá "CAMBIAR:" en este archivo.
//   - Los textos de los mensajes que vienen del servidor no están acá: los manda la API
//     (api/) o la base (triggers y procedimientos).
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
// Pantalla de tipos de contenido (catálogo con baja física).
pantallaCrud({
    api: '../../api/tipo_contenido.php',
    entidad: 'tipo_contenido',
    clave: 'tipo_cont_cod',
    nombre: 'el tipo',
    tabla: 'tabla-tipos',
    // CAMBIAR: columnas de la tabla, en orden. Cada una tiene su título <th> en la .php,
    //          en la misma posición. esc() muestra el dato tal cual (protege contra código malicioso).
    columnas: [
        f => esc(f.tipo_cont_cod),
        f => esc(f.tipo_cont_nombre),
        f => (f.tipo_cont_requiere_seguro == 1 ? 'Sí' : 'No'),
    ],
    form: 'form-tipo',
    titulo: 'titulo-form',
    tituloAlta: 'Nuevo tipo de contenido',
    tituloEdicion: f => `Editando ${f.tipo_cont_cod}`,
    // CAMBIAR: campos del formulario: 'id del <input> en la .php': 'nombre del dato en la API'.
    campos: { 'input-cod': 'tipo_cont_cod', 'input-nombre': 'tipo_cont_nombre', 'input-seguro': 'tipo_cont_requiere_seguro' },
    // CAMBIAR: orden inicial del listado (columna y ASC = ascendente / DESC = descendente).
    orden: { col: 'tipo_cont_nombre', dir: 'ASC' },
    encabezados: { 'th-cod': 'tipo_cont_cod', 'th-nombre': 'tipo_cont_nombre' },
    // CAMBIAR: cómo se borra: 'logica' = se da de baja sin borrar (se puede reactivar),
    //          'fisica' = se borra de verdad, null = sin botón de borrar.
    baja: 'fisica',
});
