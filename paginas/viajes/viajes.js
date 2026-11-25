// Pantalla de viajes (baja física: la base impide borrar un viaje con envíos o incidentes).
cargarCatalogo('input-vehiculo', 'vehiculos');
cargarCatalogo('input-chofer', 'choferes');
cargarCatalogo('filtro-chofer', 'choferes');
cargarCatalogo('input-origen', 'sucursales');
cargarCatalogo('input-destino', 'sucursales');

const kg = n => Number(n).toLocaleString('es-AR', { maximumFractionDigits: 2 });

const pantalla = pantallaCrud({
    api: '../../api/viajes.php',
    entidad: 'viajes',
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
        // carga: funcion_carga_viaje de la base
        f => `${esc(f.cantidad_envios)}<br><small>${kg(f.carga)} / ${kg(f.capacidad)} kg</small>`,
    ],
    // "Cerrar viaje" solo si ya salió y no tiene llegada (la base lo controla igual).
    accionesExtra: f => `<button data-accion="detalle" data-clave="${esc(f.viaje_id)}">Detalle</button>`
        + (f.ya_salio == 1 && !f.fecha_llegada ? ` <button data-accion="cerrar" data-clave="${esc(f.viaje_id)}">Cerrar viaje</button>` : '')
        + (esAdmin ? ` <button data-accion="cambios" data-clave="${esc(f.viaje_id)}">Ver cambios</button>` : ''),
    acciones: {
        cambios: f => { location.href = `../auditoria/auditoria.php?tabla=viaje&clave=${encodeURIComponent(f.viaje_id)}`; },
        detalle: f => { location.href = `detalle.php?viaje_id=${encodeURIComponent(f.viaje_id)}`; },
        cerrar: f => abrirCierre(f),
    },
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

// ------------------------------------------------------------ cerrar viaje
const dlgCerrar = document.getElementById('dialogo-cerrar');
let viajeACerrar = null;

function abrirCierre(f) {
    viajeACerrar = f;
    document.getElementById('titulo-cerrar').textContent = `Cerrar viaje ${f.viaje_id}`;
    document.getElementById('texto-cerrar').textContent =
        `${f.origen} → ${f.destino}. Salió el ${f.fecha_salida}. Sus ${f.cantidad_envios} envío(s) ` +
        'que no estén entregados o devueltos pasan a "En sucursal" de destino. Si alguno no se puede ' +
        'actualizar, no se cierra nada.';
    const campo = document.getElementById('input-llegada-real');
    limitarHastaAhora(campo);
    campo.value = campo.max;                                   // por defecto: ahora
    campo.min = String(f.fecha_salida).replace(' ', 'T').slice(0, 16);
    dlgCerrar.showModal();
}

document.getElementById('btn-cancelar-cierre').addEventListener('click', () => dlgCerrar.close());
document.getElementById('form-cerrar').addEventListener('submit', async e => {
    e.preventDefault();
    try {
        const r = await api('../../api/viajes.php', 'POST', {
            accion: 'cerrar',
            viaje_id: viajeACerrar.viaje_id,
            fecha_llegada: document.getElementById('input-llegada-real').value,
        });
        dlgCerrar.close();
        alert(r.mensaje);
        pantalla.listar();
    } catch (err) {
        mostrarError(err);
    }
});
