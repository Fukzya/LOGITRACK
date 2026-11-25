// ==============================================================================================
// QUÉ ES:  el comportamiento de la pantalla "Tipos de vehículo" (el diseño está en tipo_vehiculo.php).
//          Es una CONFIGURACIÓN: dice qué columnas mostrar, qué campos tiene el formulario
//          y qué filtros hay. Listar, guardar, editar y borrar lo hace paginas/crud.js,
//          igual para todas las pantallas.
// PARA CAMBIAR: buscá "CAMBIAR:" en este archivo.
//   - Los textos de los mensajes que vienen del servidor no están acá: los manda la API
//     (api/) o la base (triggers y procedimientos).
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
// Pantalla de tipos de vehículo (catálogo con baja física).
pantallaCrud({
    api: '../../api/tipo_vehiculo.php',
    entidad: 'tipo_vehiculo',
    clave: 'tipo_veh_cod',
    nombre: 'el tipo',
    tabla: 'tabla-tipos',
    // CAMBIAR: columnas de la tabla, en orden. Cada una tiene su título <th> en la .php,
    //          en la misma posición. esc() muestra el dato tal cual (protege contra código malicioso).
    columnas: [
        f => esc(f.tipo_veh_cod),
        f => esc(f.tipo_veh_nombre),
        f => esc(f.tipo_veh_capacidad_kg),
        f => (f.tipo_veh_requiere_lic_especial == 1 ? 'Sí' : 'No'),
    ],
    form: 'form-tipo',
    titulo: 'titulo-form',
    tituloAlta: 'Nuevo tipo de vehículo',
    tituloEdicion: f => `Editando ${f.tipo_veh_cod}`,
    // CAMBIAR: campos del formulario: 'id del <input> en la .php': 'nombre del dato en la API'.
    campos: {
        'input-cod': 'tipo_veh_cod',
        'input-nombre': 'tipo_veh_nombre',
        'input-capacidad': 'tipo_veh_capacidad_kg',
        'input-licencia': 'tipo_veh_requiere_lic_especial',
    },
    // CAMBIAR: orden inicial del listado (columna y ASC = ascendente / DESC = descendente).
    orden: { col: 'tipo_veh_nombre', dir: 'ASC' },
    encabezados: { 'th-cod': 'tipo_veh_cod', 'th-nombre': 'tipo_veh_nombre', 'th-capacidad': 'tipo_veh_capacidad_kg' },
    // CAMBIAR: cómo se borra: 'logica' = se da de baja sin borrar (se puede reactivar),
    //          'fisica' = se borra de verdad, null = sin botón de borrar.
    baja: 'fisica',
});
