// ==============================================================================================
// QUÉ ES:  el comportamiento de la pantalla "Historial de estados" (el diseño está en historial_estado.php).
//          Es una CONFIGURACIÓN: dice qué columnas mostrar, qué campos tiene el formulario
//          y qué filtros hay. Listar, guardar, editar y borrar lo hace paginas/crud.js,
//          igual para todas las pantallas.
// PARA CAMBIAR: buscá "CAMBIAR:" en este archivo.
//   - Los textos de los mensajes que vienen del servidor no están acá: los manda la API
//     (api/) o la base (triggers y procedimientos).
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
// Pantalla del historial de estados de los envíos.
// El historial es inmutable: solo se registran estados nuevos, no se editan ni se borran.
// Se puede abrir filtrado desde Envíos: historial_estado.php?nro_envio=123
cargarCatalogo('input-estado', 'estados');
cargarCatalogo('filtro-estado', 'estados');
cargarCatalogo('input-sucursal', 'sucursales');
cargarCatalogo('filtro-sucursal', 'sucursales');

pantallaCrud({
    api: '../../api/historial_estado.php',
    entidad: 'historial_estado',
    clave: 'id_historial',
    nombre: 'el registro',
    tabla: 'tabla-historial',
    paginacion: 'paginacion',
    // CAMBIAR: columnas de la tabla, en orden. Cada una tiene su título <th> en la .php,
    //          en la misma posición. esc() muestra el dato tal cual (protege contra código malicioso).
    columnas: [
        f => esc(f.id_historial),
        f => esc(f.nro_envio),
        f => `${esc(f.sucursal_cod)} - ${esc(f.sucursal_nombre)}`,
        f => esc(f.estado_nombre),
        f => esc(f.fecha_hora),
        f => esc(f.observacion),
    ],
    form: 'form-historial',
    titulo: 'titulo-form',
    tituloAlta: 'Registrar un estado',
    // CAMBIAR: campos del formulario: 'id del <input> en la .php': 'nombre del dato en la API'.
    campos: {
        'input-envio': 'nro_envio',
        'input-sucursal': 'sucursal_cod',
        'input-estado': 'estado',
        'input-fecha': 'fecha_hora',
        'input-observacion': 'observacion',
    },
    // CAMBIAR: filtros de arriba de la tabla: 'id del <input>': 'parámetro que recibe la API'.
    filtros: {
        'filtro-envio': 'nro_envio',
        'filtro-estado': 'estado',
        'filtro-sucursal': 'sucursal_cod',
        'filtro-fecha-desde': 'fecha_desde',
        'filtro-fecha-hasta': 'fecha_hasta',
    },
    // CAMBIAR: orden inicial del listado (columna y ASC = ascendente / DESC = descendente).
    orden: { col: 'id_historial', dir: 'DESC' },
    encabezados: { 'th-id': 'id_historial', 'th-envio': 'nro_envio', 'th-fecha': 'fecha_hora' },
    editable: false,
    // CAMBIAR: cómo se borra: 'logica' = se da de baja sin borrar (se puede reactivar),
    //          'fisica' = se borra de verdad, null = sin botón de borrar.
    baja: null,
});
