// ==============================================================================================
// QUÉ ES:  el comportamiento de la pantalla "Incidentes" (el diseño está en incidentes.php).
//          Es una CONFIGURACIÓN: dice qué columnas mostrar, qué campos tiene el formulario
//          y qué filtros hay. Listar, guardar, editar y borrar lo hace paginas/crud.js,
//          igual para todas las pantallas.
// PARA CAMBIAR: buscá "CAMBIAR:" en este archivo.
//   - Los textos de los mensajes que vienen del servidor no están acá: los manda la API
//     (api/) o la base (triggers y procedimientos).
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
// Pantalla de incidentes de los viajes (baja física).
cargarCatalogo('input-tipo', 'incidentes');
cargarCatalogo('filtro-tipo', 'incidentes');

pantallaCrud({
    api: '../../api/incidentes.php',
    entidad: 'incidentes',
    clave: 'incidente_id',
    nombre: 'el incidente',
    tabla: 'tabla-incidentes',
    paginacion: 'paginacion',
    // CAMBIAR: columnas de la tabla, en orden. Cada una tiene su título <th> en la .php,
    //          en la misma posición. esc() muestra el dato tal cual (protege contra código malicioso).
    columnas: [
        f => esc(f.incidente_id),
        f => esc(f.viaje_id),
        f => esc(f.fecha_hora),
        f => esc(f.tipo_incidente_nombre),
        f => esc(f.descripcion),
    ],
    form: 'form-incidente',
    titulo: 'titulo-form',
    tituloAlta: 'Registrar un incidente',
    tituloEdicion: f => `Editando incidente ${f.incidente_id}`,
    // CAMBIAR: campos del formulario: 'id del <input> en la .php': 'nombre del dato en la API'.
    campos: {
        'input-viaje': 'viaje_id',
        'input-fecha': 'fecha_hora',
        'input-tipo': 'tipo_incidente',
        'input-descripcion': 'descripcion',
    },
    // CAMBIAR: filtros de arriba de la tabla: 'id del <input>': 'parámetro que recibe la API'.
    filtros: {
        'filtro-viaje': 'viaje_id',
        'filtro-tipo': 'tipo',
        'filtro-fecha-desde': 'fecha_desde',
        'filtro-fecha-hasta': 'fecha_hasta',
    },
    // CAMBIAR: orden inicial del listado (columna y ASC = ascendente / DESC = descendente).
    orden: { col: 'fecha_hora', dir: 'DESC' },
    encabezados: { 'th-id': 'incidente_id', 'th-viaje': 'viaje_id', 'th-fecha': 'fecha_hora', 'th-tipo': 'tipo_incidente' },
    // CAMBIAR: cómo se borra: 'logica' = se da de baja sin borrar (se puede reactivar),
    //          'fisica' = se borra de verdad, null = sin botón de borrar.
    baja: 'fisica',
});
