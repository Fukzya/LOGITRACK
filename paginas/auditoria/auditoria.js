// ==============================================================================================
// QUÉ ES:  el comportamiento de la pantalla "Auditoría" (el diseño está en auditoria.php).
//          Es una CONFIGURACIÓN: dice qué columnas mostrar, qué campos tiene el formulario
//          y qué filtros hay. Listar, guardar, editar y borrar lo hace paginas/crud.js,
//          igual para todas las pantallas.
// PARA CAMBIAR: buscá "CAMBIAR:" en este archivo.
//   - Los textos de los mensajes que vienen del servidor no están acá: los manda la API
//     (api/) o la base (triggers y procedimientos).
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
// Pantalla de auditoría (solo admin, solo lectura). Se puede abrir filtrada desde Envíos o Viajes:
// auditoria.php?tabla=envio&clave=8113
const NOMBRES_TABLA = { envio: 'Envío', viaje: 'Viaje', usuario: 'Usuario' };
const NOMBRES_CAMPO = {
    fecha_envio: 'Fecha', peso_kg: 'Peso (kg)', tipo_cont_cod: 'Tipo de contenido',
    remitente_dni: 'Remitente (DNI)', destinatario_dni: 'Destinatario (DNI)',
    sucursal_orig_cod: 'Sucursal de origen', sucursal_dest_cod: 'Sucursal de destino',
    direccion_entrega_id: 'Dirección de entrega', activo: 'Activo',
    fecha_salida: 'Salida', fecha_llegada: 'Llegada real', sucursal_origen_cod: 'Sale de',
    sucursal_destino_cod: 'Llega a', vehiculo_patente: 'Vehículo', chofer_legajo: 'Chofer (legajo)',
    username: 'Usuario', rol: 'Rol', '*': 'Se eliminó',
};
const valor = v => v === null ? '<em>vacío</em>' : esc(v);

pantallaCrud({
    api: '../../api/auditoria.php',
    clave: 'auditoria_id',
    tabla: 'tabla-auditoria',
    paginacion: 'paginacion',
    // CAMBIAR: columnas de la tabla, en orden. Cada una tiene su título <th> en la .php,
    //          en la misma posición. esc() muestra el dato tal cual (protege contra código malicioso).
    columnas: [
        f => esc(f.fecha_hora),
        f => `${esc(NOMBRES_TABLA[f.tabla] ?? f.tabla)} ${esc(f.clave)}`,
        f => esc(NOMBRES_CAMPO[f.campo] ?? f.campo),
        f => valor(f.valor_anterior),
        f => valor(f.valor_nuevo),
        f => esc(f.usuario),
    ],
    // CAMBIAR: filtros de arriba de la tabla: 'id del <input>': 'parámetro que recibe la API'.
    filtros: {
        'filtro-tabla': 'tabla',
        'filtro-clave': 'clave',
        'filtro-usuario': 'usuario',
        'filtro-fecha-desde': 'fecha_desde',
        'filtro-fecha-hasta': 'fecha_hasta',
    },
    // CAMBIAR: orden inicial del listado (columna y ASC = ascendente / DESC = descendente).
    orden: { col: 'auditoria_id', dir: 'DESC' },
    encabezados: { 'th-fecha': 'fecha_hora' },
    editable: false,
    // CAMBIAR: cómo se borra: 'logica' = se da de baja sin borrar (se puede reactivar),
    //          'fisica' = se borra de verdad, null = sin botón de borrar.
    baja: null,
});
