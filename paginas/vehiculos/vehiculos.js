// ==============================================================================================
// QUÉ ES:  el comportamiento de la pantalla "Vehículos" (el diseño está en vehiculos.php).
//          Es una CONFIGURACIÓN: dice qué columnas mostrar, qué campos tiene el formulario
//          y qué filtros hay. Listar, guardar, editar y borrar lo hace paginas/crud.js,
//          igual para todas las pantallas.
// PARA CAMBIAR: buscá "CAMBIAR:" en este archivo.
//   - Los textos de los mensajes que vienen del servidor no están acá: los manda la API
//     (api/) o la base (triggers y procedimientos).
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
// Pantalla de vehículos (baja lógica).
cargarCatalogo('input-tipo', 'tipos_vehiculo');
cargarCatalogo('filtro-tipo', 'tipos_vehiculo');

pantallaCrud({
    api: '../../api/vehiculos.php',
    entidad: 'vehiculos',
    clave: 'vehiculo_patente',
    nombre: 'el vehículo',
    tabla: 'tabla-vehiculos',
    paginacion: 'paginacion',
    // CAMBIAR: columnas de la tabla, en orden. Cada una tiene su título <th> en la .php,
    //          en la misma posición. esc() muestra el dato tal cual (protege contra código malicioso).
    columnas: [
        f => esc(f.vehiculo_patente),
        f => esc(f.marca),
        f => esc(f.modelo),
        f => esc(f.anio),
        f => esc(f.tipo_veh_nombre),
        celdaEstado,
    ],
    form: 'form-vehiculo',
    titulo: 'titulo-form',
    tituloAlta: 'Nuevo vehículo',
    tituloEdicion: f => `Editando vehículo ${f.vehiculo_patente}`,
    // CAMBIAR: campos del formulario: 'id del <input> en la .php': 'nombre del dato en la API'.
    campos: {
        'input-patente': 'vehiculo_patente',
        'input-marca': 'marca',
        'input-modelo': 'modelo',
        'input-anio': 'anio',
        'input-tipo': 'tipo_veh_cod',
    },
    // CAMBIAR: filtros de arriba de la tabla: 'id del <input>': 'parámetro que recibe la API'.
    filtros: {
        'filtro-patente': 'patente',
        'filtro-marca': 'marca',
        'filtro-tipo': 'tipo_veh_cod',
        'filtro-mostrar-inactivos': 'mostrar_inactivos',
    },
    // CAMBIAR: orden inicial del listado (columna y ASC = ascendente / DESC = descendente).
    orden: { col: 'vehiculo_patente', dir: 'ASC' },
    encabezados: {
        'th-patente': 'vehiculo_patente', 'th-marca': 'marca', 'th-modelo': 'modelo',
        'th-anio': 'anio', 'th-tipo': 'tipo_veh_nombre',
    },
    // CAMBIAR: cómo se borra: 'logica' = se da de baja sin borrar (se puede reactivar),
    //          'fisica' = se borra de verdad, null = sin botón de borrar.
    baja: 'logica',
});
