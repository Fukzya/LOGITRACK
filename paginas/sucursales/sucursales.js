// ==============================================================================================
// QUÉ ES:  el comportamiento de la pantalla "Sucursales" (el diseño está en sucursales.php).
//          Es una CONFIGURACIÓN: dice qué columnas mostrar, qué campos tiene el formulario
//          y qué filtros hay. Listar, guardar, editar y borrar lo hace paginas/crud.js,
//          igual para todas las pantallas.
// PARA CAMBIAR: buscá "CAMBIAR:" en este archivo.
//   - Los textos de los mensajes que vienen del servidor no están acá: los manda la API
//     (api/) o la base (triggers y procedimientos).
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
// Pantalla de sucursales (baja lógica).
cargarCatalogo('input-localidad', 'localidades');

pantallaCrud({
    api: '../../api/sucursales.php',
    entidad: 'sucursales',
    clave: 'sucursal_cod',
    nombre: 'la sucursal',
    tabla: 'tabla-sucursales',
    paginacion: 'paginacion',
    // CAMBIAR: columnas de la tabla, en orden. Cada una tiene su título <th> en la .php,
    //          en la misma posición. esc() muestra el dato tal cual (protege contra código malicioso).
    columnas: [
        f => esc(f.sucursal_cod),
        f => esc(f.sucursal_nombre),
        f => esc(f.localidad_nombre),
        f => esc(f.provincia_nombre),
        f => esc(f.direccion),
        celdaEstado,
    ],
    form: 'form-sucursal',
    titulo: 'titulo-form',
    tituloAlta: 'Nueva sucursal',
    tituloEdicion: f => `Editando sucursal ${f.sucursal_cod}`,
    // CAMBIAR: campos del formulario: 'id del <input> en la .php': 'nombre del dato en la API'.
    campos: {
        'input-cod': 'sucursal_cod',
        'input-nombre': 'sucursal_nombre',
        'input-localidad': 'sucursal_localidad_cod',
        'input-direccion': 'direccion',
    },
    // CAMBIAR: filtros de arriba de la tabla: 'id del <input>': 'parámetro que recibe la API'.
    filtros: { 'filtro-nombre': 'nombre', 'filtro-mostrar-inactivos': 'mostrar_inactivos' },
    // CAMBIAR: orden inicial del listado (columna y ASC = ascendente / DESC = descendente).
    orden: { col: 'sucursal_cod', dir: 'ASC' },
    encabezados: { 'th-cod': 'sucursal_cod', 'th-nombre': 'sucursal_nombre', 'th-localidad': 'localidad_nombre', 'th-provincia': 'provincia_nombre' },
    // CAMBIAR: cómo se borra: 'logica' = se da de baja sin borrar (se puede reactivar),
    //          'fisica' = se borra de verdad, null = sin botón de borrar.
    baja: 'logica',
});
