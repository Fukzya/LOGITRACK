// Pantalla de viajes (baja física: la base impide borrar un viaje con envíos o incidentes).
cargarCatalogo('input-vehiculo', 'vehiculos');
cargarCatalogo('input-chofer', 'choferes');
cargarCatalogo('filtro-chofer', 'choferes');
cargarCatalogo('input-origen', 'sucursales');
cargarCatalogo('input-destino', 'sucursales');

pantallaCrud({
    api: '../../api/viajes.php',
    clave: 'viaje_id',
    nombre: 'el viaje',
    tabla: 'tabla-viajes',
    paginacion: 'paginacion',
    columnas: [
        f => esc(f.viaje_id),
        f => esc(f.fecha_salida),
        f => (f.fecha_llegada ? esc(f.fecha_llegada) : `<em>En curso</em><br><small>Estimada: ${esc(f.llegada_estimada)}</small>`),
        f => `${esc(f.origen)}<br><small>→ ${esc(f.destino)}</small>`,
        f => `${esc(f.vehiculo_patente)}<br><small>${esc(f.vehiculo)}</small>`,
        f => `${esc(f.chofer_nombre)}<br><small>Legajo ${esc(f.chofer_legajo)}</small>`,
        f => esc(f.cantidad_envios),
    ],
    form: 'form-viaje',
    titulo: 'titulo-form',
    tituloAlta: 'Nuevo viaje',
    tituloEdicion: f => `Editando viaje ${f.viaje_id}`,
    campos: {
        'input-salida': 'fecha_salida',
        'input-llegada': 'fecha_llegada',
        'input-origen': 'sucursal_origen_cod',
        'input-destino': 'sucursal_destino_cod',
        'input-vehiculo': 'vehiculo_patente',
        'input-chofer': 'chofer_legajo',
    },
    filtros: {
        'filtro-fecha-desde': 'fecha_desde',
        'filtro-fecha-hasta': 'fecha_hasta',
        'filtro-chofer': 'chofer_legajo',
        'filtro-solo-activos': 'solo_activos',
    },
    orden: { col: 'viaje_id', dir: 'DESC' },
    encabezados: { 'th-id': 'viaje_id', 'th-salida': 'fecha_salida', 'th-llegada': 'fecha_llegada' },
    baja: 'fisica',
});
