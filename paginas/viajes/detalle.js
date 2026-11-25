// ==============================================================================================
// QUÉ ES:  el comportamiento de la pantalla "Detalle de viaje" (el diseño está en detalle.php).
// PARA CAMBIAR: buscá "CAMBIAR:" en este archivo.
//   - Los textos de los mensajes que vienen del servidor no están acá: los manda la API
//     (api/) o la base (triggers y procedimientos).
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
// Detalle de un viaje: datos, carga (funcion_carga_viaje), envíos que lleva, agregar envíos
// pendientes (procedimiento_asignar_envio_a_viaje, en una transacción) y cerrar el viaje
// (procedimiento_cerrar_viaje). Se abre como detalle.php?viaje_id=123
const $ = id => document.getElementById(id);
const API = '../../api/viajes.php';
const viajeId = new URLSearchParams(location.search).get('viaje_id');
// CAMBIAR: formato de los números ('es-AR' = coma decimal).
const kg = n => Number(n).toLocaleString('es-AR', { maximumFractionDigits: 2 });
let viaje = null;

async function cargar() {
    try {
        viaje = await api(`${API}?detalle=${encodeURIComponent(viajeId)}`);
    } catch (err) {
        mostrarError(err);
        return;
    }
    const v = viaje;
    const estado = v.fecha_llegada ? `Llegó el ${v.fecha_llegada}`
                 : v.ya_salio == 1  ? `En viaje (llegada estimada ${v.llegada_estimada})`
                 :                    `Programado (sale el ${v.fecha_salida})`;
    $('titulo-viaje').textContent = `Viaje ${v.viaje_id}`;
    $('datos-viaje').innerHTML = [
        ['Ruta', `${esc(v.origen)}<br>→ ${esc(v.destino)}`],
        ['Salida', `${esc(v.fecha_salida)}<br><small>Llegada estimada: ${esc(v.llegada_estimada)}</small>`],
        ['Estado', esc(estado)],
        ['Vehículo', `${esc(v.vehiculo_patente)}<br><small>${esc(v.vehiculo)}</small>`],
        ['Chofer', esc(v.chofer_nombre)],
        ['Envíos', esc(v.envios.length)],
    ].map(([t, html]) => `<div><div class="text-xs text-on-surface-variant">${t}</div><div>${html}</div></div>`).join('');

    const porcentaje = v.capacidad > 0 ? Math.min(100, 100 * v.carga / v.capacidad) : 0;
    $('barra-carga').style.width = porcentaje + '%';
    $('texto-carga').textContent = `${kg(v.carga)} de ${kg(v.capacidad)} kg (libres: ${kg(v.capacidad - v.carga)} kg)`;

    $('btn-cerrar-viaje').hidden = !(v.ya_salio == 1 && !v.fecha_llegada);

    $('tabla-envios').innerHTML = v.envios.map(e => `
        <tr>
            <td><a class="text-primary underline" href="../historial_estado/historial_estado.php?nro_envio=${encodeURIComponent(e.nro_envio)}" title="Ver historial">${esc(e.nro_envio)}</a></td>
            <td>${e.tramo == 2 ? '2 (trasbordo)' : '1'}</td>
            <td>${esc(kg(e.peso_kg))}</td>
            <td>${esc(e.remitente)}<br><small>→ ${esc(e.destinatario)}</small></td>
            <td>${esc(e.destino_envio)}</td>
            <td>${esc(e.ultimo_estado ?? 'Sin estados')}</td>
        </tr>`).join('') || '<tr><td colspan="6">El viaje no lleva envíos.</td></tr>';

    // Agregar envíos: solo si todavía no salió.
    const puedeAgregar = v.ya_salio != 1 && !v.fecha_llegada;
    $('tarjeta-pendientes').hidden = !puedeAgregar;
    if (puedeAgregar) {
        $('tabla-pendientes').innerHTML = v.pendientes.map(p => `
            <tr>
                <td><input type="checkbox" class="w-4 h-4 accent-primary" value="${esc(p.nro_envio)}" data-peso="${esc(p.peso_kg)}"></td>
                <td>${esc(p.nro_envio)}</td>
                <td>${esc(p.fecha_envio)}</td>
                <td>${esc(kg(p.peso_kg))}</td>
                <td>${esc(p.remitente)}<br><small>→ ${esc(p.destinatario)}</small></td>
                <td>${esc(p.destino_envio)}${p.va_a_la_provincia == 1 ? '<br><small>Va a la provincia de destino</small>' : ''}</td>
                <td>${esc(p.motivo)}</td>
            </tr>`).join('') || '<tr><td colspan="7">No hay envíos pendientes para este viaje.</td></tr>';
        actualizarSeleccion();
    }
}

// Muestra cuánto suma lo elegido. Si se pasa de la capacidad lo avisa, pero la base es la que decide.
function actualizarSeleccion() {
    const elegidos = [...document.querySelectorAll('#tabla-pendientes input:checked')];
    const peso = elegidos.reduce((s, c) => s + Number(c.dataset.peso), 0);
    const libre = viaje.capacidad - viaje.carga;
    $('btn-agregar').disabled = elegidos.length === 0;
    $('btn-agregar').textContent = elegidos.length
        ? `Agregar ${elegidos.length} (${kg(peso)} kg${peso > libre ? ', supera la capacidad libre' : ''})`
        : 'Agregar seleccionados';
}
$('tabla-pendientes').addEventListener('change', actualizarSeleccion);

$('btn-agregar').addEventListener('click', async () => {
    const envios = [...document.querySelectorAll('#tabla-pendientes input:checked')].map(c => Number(c.value));
    if (!confirm(`¿Agregar ${envios.length} envío(s) al viaje ${viaje.viaje_id}?\nSi alguno no se puede asignar, no se agrega ninguno.`)) return;
    try {
        alert((await api(API, 'POST', { accion: 'agregar_envios', viaje_id: viaje.viaje_id, envios })).mensaje);
    } catch (err) {
        mostrarError(err);
    }
    cargar();
});

// ------------------------------------------------------------ cerrar viaje
const dlgCerrar = $('dialogo-cerrar');
$('btn-cerrar-viaje').addEventListener('click', () => {
    const campo = $('input-llegada-real');
    limitarHastaAhora(campo);
    campo.value = campo.max;                                   // por defecto: ahora
    campo.min = String(viaje.fecha_salida).replace(' ', 'T').slice(0, 16);
    dlgCerrar.showModal();
});
$('btn-cancelar-cierre').addEventListener('click', () => dlgCerrar.close());
$('form-cerrar').addEventListener('submit', async e => {
    e.preventDefault();
    try {
        const r = await api(API, 'POST', { accion: 'cerrar', viaje_id: viaje.viaje_id, fecha_llegada: $('input-llegada-real').value });
        dlgCerrar.close();
        alert(r.mensaje);
        cargar();
    } catch (err) {
        mostrarError(err);
    }
});

if (viajeId) cargar(); else location.href = 'viajes.php';
