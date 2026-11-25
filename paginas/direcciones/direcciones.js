// ==============================================================================================
// QUÉ ES:  el comportamiento de la pantalla "Direcciones" (el diseño está en direcciones.php).
//          Es una CONFIGURACIÓN: dice qué columnas mostrar, qué campos tiene el formulario
//          y qué filtros hay. Listar, guardar, editar y borrar lo hace paginas/crud.js,
//          igual para todas las pantallas.
// PARA CAMBIAR: buscá "CAMBIAR:" en este archivo.
//   - Los textos de los mensajes que vienen del servidor no están acá: los manda la API
//     (api/) o la base (triggers y procedimientos).
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
// Pantalla de direcciones (baja lógica).
cargarCatalogo('input-localidad', 'localidades');
cargarCatalogo('filtro-localidad', 'localidades');

pantallaCrud({
    api: '../../api/direcciones.php',
    entidad: 'direcciones',
    clave: 'direccion_id',
    nombre: 'la dirección',
    tabla: 'tabla-direcciones',
    paginacion: 'paginacion',
    // CAMBIAR: columnas de la tabla, en orden. Cada una tiene su título <th> en la .php,
    //          en la misma posición. esc() muestra el dato tal cual (protege contra código malicioso).
    columnas: [
        f => esc(f.direccion_id),
        f => esc(f.calle_y_numero),
        f => esc(f.localidad_nombre),
        f => `${esc(f.cliente_dni)} - ${esc(f.cliente_nombre)}`,
        celdaEstado,
    ],
    form: 'form-direccion',
    titulo: 'titulo-form',
    tituloAlta: 'Nueva dirección',
    tituloEdicion: f => `Editando dirección ${f.direccion_id}`,
    // CAMBIAR: campos del formulario: 'id del <input> en la .php': 'nombre del dato en la API'.
    campos: { 'input-calle': 'calle_y_numero', 'input-localidad': 'localidad_cod', 'input-dni': 'cliente_dni' },
    // CAMBIAR: filtros de arriba de la tabla: 'id del <input>': 'parámetro que recibe la API'.
    filtros: {
        'filtro-calle': 'calle',
        'filtro-dni': 'cliente_dni',
        'filtro-localidad': 'localidad_cod',
        'filtro-mostrar-inactivos': 'mostrar_inactivos',
    },
    // CAMBIAR: orden inicial del listado (columna y ASC = ascendente / DESC = descendente).
    orden: { col: 'direccion_id', dir: 'ASC' },
    encabezados: { 'th-id': 'direccion_id', 'th-calle': 'calle_y_numero', 'th-localidad': 'localidad_nombre', 'th-dni': 'cliente_dni' },
    // CAMBIAR: cómo se borra: 'logica' = se da de baja sin borrar (se puede reactivar),
    //          'fisica' = se borra de verdad, null = sin botón de borrar.
    baja: 'logica',
});
