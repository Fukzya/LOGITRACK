// ==============================================================================================
// QUÉ ES:  el comportamiento de la pantalla "Choferes" (el diseño está en choferes.php).
//          Es una CONFIGURACIÓN: dice qué columnas mostrar, qué campos tiene el formulario
//          y qué filtros hay. Listar, guardar, editar y borrar lo hace paginas/crud.js,
//          igual para todas las pantallas.
// PARA CAMBIAR: buscá "CAMBIAR:" en este archivo.
//   - Los textos de los mensajes que vienen del servidor no están acá: los manda la API
//     (api/) o la base (triggers y procedimientos).
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
// Pantalla de choferes (baja lógica).
cargarCatalogo('input-licencia', 'licencias');
cargarCatalogo('filtro-licencia', 'licencias');

pantallaCrud({
    api: '../../api/choferes.php',
    entidad: 'choferes',
    clave: 'chofer_legajo',
    nombre: 'el chofer',
    tabla: 'tabla-choferes',
    paginacion: 'paginacion',
    // CAMBIAR: columnas de la tabla, en orden. Cada una tiene su título <th> en la .php,
    //          en la misma posición. esc() muestra el dato tal cual (protege contra código malicioso).
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
    // CAMBIAR: campos del formulario: 'id del <input> en la .php': 'nombre del dato en la API'.
    campos: { 'input-nombre': 'nombre', 'input-apellido': 'apellido', 'input-licencia': 'chofer_tipo_licencia' },
    // CAMBIAR: filtros de arriba de la tabla: 'id del <input>': 'parámetro que recibe la API'.
    filtros: {
        'filtro-nombre': 'nombre',
        'filtro-apellido': 'apellido',
        'filtro-licencia': 'tipo_licencia',
        'filtro-mostrar-inactivos': 'mostrar_inactivos',
    },
    // CAMBIAR: orden inicial del listado (columna y ASC = ascendente / DESC = descendente).
    orden: { col: 'chofer_legajo', dir: 'ASC' },
    encabezados: { 'th-legajo': 'chofer_legajo', 'th-nombre': 'nombre', 'th-apellido': 'apellido', 'th-licencia': 'chofer_tipo_licencia' },
    // CAMBIAR: cómo se borra: 'logica' = se da de baja sin borrar (se puede reactivar),
    //          'fisica' = se borra de verdad, null = sin botón de borrar.
    baja: 'logica',
});
