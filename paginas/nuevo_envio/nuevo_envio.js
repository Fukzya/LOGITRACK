// ==============================================================================================
// QUÉ ES:  el comportamiento de la pantalla "Nuevo envío" (el diseño está en nuevo_envio.php).
// PARA CAMBIAR: buscá "CAMBIAR:" en este archivo.
//   - Los textos de los mensajes que vienen del servidor no están acá: los manda la API
//     (api/) o la base (triggers y procedimientos).
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
// Asistente "Nuevo envío": personas -> paquete -> viaje -> confirmar.
// Nada se guarda hasta el último paso (salvo un cliente o dirección nuevos, que se dan de alta en el
// momento). Al confirmar, api/asistente_envio.php crea envío + viaje + asignación en una transacción.

const $ = id => document.getElementById(id);

const estado = {
    paso: 1,
    remitente: null,
    destinatario: null,
    direcciones: [],     // del destinatario
    direccion: null,     // la elegida
    viaje: { modo: null, ids: [] },   // ids: [viaje] directo o [viaje1, viaje2] con trasbordo
    rutas: { opciones: [], viajes: [] }, // lo que devuelve procedimiento_buscar_rutas
    recursos: null,      // vehículos y choferes libres para un viaje nuevo
};
let sucursales = [];               // [{cod, nombre, provincia_cod}]
const provincias = new Map();      // cod -> nombre
const sugerida = { orig: true, dest: true }; // false cuando el usuario elige a mano

// ------------------------------------------------------------ catálogos
cargarCatalogo('input-tipo', 'tipos_contenido');
api('../../api/catalogos.php?tipo=provincias').then(ps => ps.forEach(p => provincias.set(String(p.cod), p.nombre)));
api('../../api/catalogos.php?tipo=sucursales').then(ss => {
    sucursales = ss;
    for (const id of ['input-suc-orig', 'input-suc-dest', 'nv-origen', 'nv-destino']) llenarSelect($(id), ss); // por provincia
}).catch(mostrarError);

const hoy = new Date();
const isoLocal = d => new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString();
$('input-fecha').value = isoLocal(hoy).slice(0, 10);
$('nv-salida').min = isoLocal(hoy).slice(0, 16);

// ------------------------------------------------------------ formato
// CAMBIAR: formato de números y fechas ('es-AR' = coma decimal y día/mes/año).
const kg = n => Number(n).toLocaleString('es-AR', { maximumFractionDigits: 2 }) + ' kg';
const fechaHora = f => new Date(f.replace(' ', 'T')).toLocaleString('es-AR',
    { weekday: 'short', day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
const nombreCliente = c => `${c.nombre} ${c.apellido} (DNI ${formatoDni(c.cliente_dni)})`;
const textoOpcion = id => { const s = $(id); return s.selectedIndex > 0 ? s.options[s.selectedIndex].text : ''; };

// ------------------------------------------------------------ navegación entre pasos
function mostrarPaso(n) {
    estado.paso = n;
    document.querySelectorAll('[data-paso]').forEach(s => { s.hidden = Number(s.dataset.paso) !== n; });
    document.querySelectorAll('[data-indicador]').forEach(li => {
        const i = Number(li.dataset.indicador);
        const circulo = li.querySelector('span');
        li.className = 'flex items-center gap-3 rounded-xl px-4 py-3 border-2 ' +
            (i === n ? 'bg-surface-container-lowest border-primary text-primary'
                     : i < n ? 'bg-surface-container-lowest border-transparent text-on-surface'
                             : 'bg-surface-container-lowest border-transparent text-on-surface-variant');
        circulo.className = 'w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0 ' +
            (i === n ? 'bg-primary text-on-primary' : i < n ? 'bg-green-600 text-white' : 'bg-surface-container');
        circulo.innerHTML = i < n ? '<span class="material-symbols-outlined text-[18px]">check</span>' : String(i);
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Cada paso valida lo suyo antes de avanzar.
const validar = {
    1() {
        if (!estado.remitente)    return 'Elegí el remitente.';
        if (!estado.destinatario) return 'Elegí el destinatario.';
        if (!estado.direccion)    return 'Elegí la dirección de entrega (o agregá una).';
        return null;
    },
    2() {
        if (!$('form-paquete').reportValidity()) return '';
        if ($('input-suc-orig').value === $('input-suc-dest').value) return 'La sucursal de origen y la de destino tienen que ser distintas.';
        return null;
    },
    3() {
        const v = estado.viaje;
        if (!v.modo) return 'Elegí un viaje, creá uno nuevo o elegí "Asignar más tarde".';
        if (v.modo === 'nuevo' && !$('form-viaje-nuevo').reportValidity()) return '';
        return null;
    },
};

document.addEventListener('click', e => {
    const sig = e.target.closest('[data-siguiente]');
    const ant = e.target.closest('[data-anterior]');
    if (sig) {
        const error = validar[estado.paso]();
        if (error !== null) { if (error) alert(error); return; }
        const n = Number(sig.dataset.siguiente);
        if (n === 2) sugerirSucursales();
        if (n === 3) cargarViajes();
        if (n === 4) armarResumen();
        mostrarPaso(n);
    }
    if (ant) mostrarPaso(Number(ant.dataset.anterior));
});

// ------------------------------------------------------------ paso 1: personas
selectorCliente($('sel-remitente'), {
    alElegir: c => { estado.remitente = c; sugerida.orig = true; },
});

selectorCliente($('sel-destinatario'), {
    conDireccion: true,
    alElegir: (c, idDireccionNueva) => {
        estado.destinatario = c;
        estado.direccion = null;
        sugerida.dest = true;
        $('form-direccion').hidden = true;
        $('bloque-direcciones').hidden = !c;
        if (c) cargarDirecciones(idDireccionNueva);
    },
});

async function cargarDirecciones(elegir = null) {
    const caja = $('lista-direcciones');
    caja.innerHTML = '<p class="text-sm text-on-surface-variant">Buscando direcciones…</p>';
    try {
        const r = await api(`../../api/direcciones.php?cliente_dni=${estado.destinatario.cliente_dni}`);
        estado.direcciones = r.datos;
    } catch (err) { mostrarError(err); return; }

    if (!estado.direcciones.length) {
        caja.innerHTML = '<p class="text-sm text-on-surface-variant">Este cliente no tiene direcciones activas: agregá una.</p>';
        $('btn-otra-direccion').click();
        return;
    }
    // Si hay una sola (o se acaba de crear), queda elegida.
    const id = elegir ?? (estado.direcciones.length === 1 ? estado.direcciones[0].direccion_id : null);
    estado.direccion = estado.direcciones.find(d => String(d.direccion_id) === String(id)) || null;
    caja.innerHTML = estado.direcciones.map(d => `
        <label class="flex items-center gap-3 rounded-lg border border-surface-container px-4 py-2.5 cursor-pointer hover:bg-[#fffbf5] has-[:checked]:border-primary has-[:checked]:bg-[#fff7ed]">
            <input type="radio" name="direccion" value="${esc(d.direccion_id)}" class="accent-primary" ${estado.direccion === d ? 'checked' : ''}/>
            <span class="text-sm text-on-surface">${esc(d.calle_y_numero)}<span class="text-on-surface-variant"> · ${esc(d.localidad_nombre)}</span></span>
        </label>`).join('');
}

$('lista-direcciones').addEventListener('change', e => {
    estado.direccion = estado.direcciones.find(d => String(d.direccion_id) === e.target.value) || null;
    sugerida.dest = true;
});

$('btn-otra-direccion').addEventListener('click', async () => {
    const form = $('form-direccion');
    form.reset();
    form.hidden = false;
    const sel = $('dir-localidad');
    if (sel.options.length === 1) (await localidades()).forEach(o => sel.add(new Option(o.nombre, o.cod)));
    form.calle_y_numero.focus();
});
$('btn-cancelar-direccion').addEventListener('click', () => { $('form-direccion').hidden = true; });

$('form-direccion').addEventListener('submit', async e => {
    e.preventDefault();
    const form = e.target;
    if (!form.reportValidity()) return;
    try {
        const r = await api('../../api/direcciones.php', 'POST', {
            calle_y_numero: form.calle_y_numero.value,
            localidad_cod: form.localidad_cod.value,
            cliente_dni: estado.destinatario.cliente_dni,
        });
        form.hidden = true;
        await cargarDirecciones(r.id);
    } catch (err) { mostrarError(err); }
});

// ------------------------------------------------------------ paso 2: paquete
// Sugiere la primera sucursal de la provincia del remitente (origen) y de la dirección (destino).
function sugerirSucursales() {
    const sugerir = (clave, selectId, pistaId, provincia, quien) => {
        if (!sugerida[clave]) return;
        const s = sucursales.find(x => String(x.provincia_cod) === String(provincia));
        const nombreProv = provincias.get(String(provincia)) || 'esa provincia';
        $(selectId).value = s ? s.cod : '';
        $(pistaId).textContent = s ? `Sugerida: está en ${nombreProv}, como ${quien}.`
                                   : `No hay sucursales en ${nombreProv}: elegí la más cercana.`;
    };
    sugerir('orig', 'input-suc-orig', 'pista-orig', estado.remitente.provincia_cod, 'el remitente');
    sugerir('dest', 'input-suc-dest', 'pista-dest', estado.direccion.provincia_cod, 'la dirección de entrega');
    mostrarOtras('orig');
    mostrarOtras('dest');
}

// Debajo de cada sucursal: las otras de la misma provincia, como botones para elegirlas con un clic.
const camposSucursal = {
    orig: { select: 'input-suc-orig', pista: 'pista-orig', otras: 'otras-orig' },
    dest: { select: 'input-suc-dest', pista: 'pista-dest', otras: 'otras-dest' },
};
const sinProvincia = nombre => nombre.replace(/ \([^)]*\)$/, ''); // "046 - Sucursal Puerto 46"

function mostrarOtras(clave) {
    const c = camposSucursal[clave];
    const elegida = sucursales.find(s => s.cod === $(c.select).value);
    const caja = $(c.otras);
    if (!elegida) { caja.innerHTML = ''; return; }
    const otras = sucursales.filter(s => s.provincia_cod === elegida.provincia_cod && s.cod !== elegida.cod);
    caja.innerHTML = otras.length
        ? `<span class="text-xs text-on-surface-variant">Otras en ${esc(elegida.grupo)}:</span>` +
          otras.map(s => `<button type="button" data-sucursal="${esc(s.cod)}" data-campo="${clave}"
                 class="text-xs whitespace-nowrap rounded-full border border-primary/40 bg-white px-2.5 py-1 text-primary hover:bg-[#fef3c7]">${esc(sinProvincia(s.nombre))}</button>`).join('')
        : `<span class="text-xs text-on-surface-variant">Es la única sucursal en ${esc(elegida.grupo)}.</span>`;
}

function elegirSucursalAMano(clave) {
    sugerida[clave] = false;
    $(camposSucursal[clave].pista).textContent = '';
    mostrarOtras(clave);
}

for (const clave of Object.keys(camposSucursal)) {
    $(camposSucursal[clave].select).addEventListener('change', () => elegirSucursalAMano(clave));
    $(camposSucursal[clave].otras).addEventListener('click', e => {
        const b = e.target.closest('[data-sucursal]');
        if (!b) return;
        $(camposSucursal[clave].select).value = b.dataset.sucursal;
        elegirSucursalAMano(clave);
    });
}

// ------------------------------------------------------------ paso 3: viaje
const peso = () => Number($('input-peso').value);
const llegadaEstimada = salida => { // misma regla que la columna calculada de la base: salida + 24 h
    const d = new Date(salida.replace(' ', 'T'));
    d.setHours(d.getHours() + 24);
    return isoLocal(d).slice(0, 16).replace('T', ' ');
};
const viajePorId = id => estado.rutas.viajes.find(v => String(v.viaje_id) === String(id));
const claveRuta = ids => ids.join('-');

function esperaTexto(desde, hasta) {
    const h = Math.round((new Date(hasta.replace(' ', 'T')) - new Date(desde.replace(' ', 'T'))) / 36e5);
    return h >= 24 ? `${Math.floor(h / 24)} d ${h % 24} h` : `${h} h`;
}

async function cargarViajes() {
    for (const id of ['lista-directos', 'lista-trasbordos', 'lista-otros']) {
        $(id).innerHTML = '<p class="text-sm text-on-surface-variant">Buscando viajes…</p>';
    }
    const params = new URLSearchParams({
        rutas: 1, sucursal_orig: $('input-suc-orig').value, sucursal_dest: $('input-suc-dest').value, peso: peso(),
    });
    try {
        estado.rutas = await api(`../../api/viajes.php?${params}`);
    } catch (err) { mostrarError(err); return; }

    // Si la ruta elegida antes ya no está (cambió el peso o las sucursales), se desmarca.
    const v = estado.viaje;
    if (v.modo === 'existente') {
        const sigue = estado.rutas.opciones.some(o => claveRuta(idsDe(o)) === claveRuta(v.ids))
                   || (v.ids.length === 1 && viajePorId(v.ids[0]) && Number(viajePorId(v.ids[0]).libre) >= peso());
        if (!sigue) estado.viaje = { modo: null, ids: [] };
    }

    // Viaje nuevo: por defecto, directo entre las sucursales del envío.
    if (!$('nv-origen').value)  $('nv-origen').value  = $('input-suc-orig').value;
    if (!$('nv-destino').value) $('nv-destino').value = $('input-suc-dest').value;

    const directos   = estado.rutas.opciones.filter(o => o.tipo === 'DIRECTO');
    const trasbordos = estado.rutas.opciones.filter(o => o.tipo === 'TRASBORDO');
    $('cuenta-directos').textContent   = `(${directos.length})`;
    $('cuenta-trasbordos').textContent = `(${trasbordos.length})`;
    $('lista-directos').innerHTML = directos.length
        ? directos.map(tarjetaDirecto).join('')
        : '<p class="text-sm text-on-surface-variant">No hay viajes directos programados entre esas provincias.</p>';
    $('lista-trasbordos').innerHTML = trasbordos.length
        ? trasbordos.map(tarjetaTrasbordo).join('')
        : '<p class="text-sm text-on-surface-variant">No hay combinaciones con un trasbordo.</p>';
    pintarOtros();
    marcarSeleccion();
}

const idsDe = o => (o.viaje2_id ? [o.viaje1_id, o.viaje2_id] : [o.viaje1_id]);

// Un tramo (un viaje) dentro de una tarjeta.
function tramoHtml(v, titulo = '') {
    const pct = Math.min(100, Math.round(Number(v.carga) / Number(v.capacidad) * 100));
    return `
        <div class="flex flex-col gap-1">
            <div class="flex items-center justify-between gap-2">
                <span class="text-sm font-bold text-on-surface">${titulo}Viaje ${esc(v.viaje_id)}</span>
                <span class="text-xs text-on-surface-variant">${esc(v.tipo_veh_nombre)} ${esc(v.vehiculo_patente)}</span>
            </div>
            <div class="text-sm text-on-surface">${esc(v.origen)} <span class="text-primary">→</span> ${esc(v.destino)}</div>
            <div class="text-xs text-on-surface-variant">Sale ${esc(fechaHora(v.fecha_salida))} · llega aprox. ${esc(fechaHora(v.llegada_estimada))}</div>
            <div class="text-xs text-on-surface-variant">${esc(v.chofer_nombre)} · ${esc(v.cantidad_envios)} envío(s) · quedan ${esc(kg(v.libre))}</div>
            <div class="h-1 rounded-full bg-surface-container-high overflow-hidden"><div class="h-full ${pct > 85 ? 'bg-red-500' : 'bg-primary'}" style="width:${Math.max(pct, 2)}%"></div></div>
        </div>`;
}

const etiquetaExacta = (exacta, texto) => exacta
    ? `<span class="text-xs font-semibold text-green-800 bg-green-200 rounded-full px-2 py-0.5">${texto}</span>`
    : '<span class="text-xs font-semibold text-amber-800 bg-amber-100 rounded-full px-2 py-0.5">Misma provincia, otra sucursal</span>';

function tarjetaDirecto(o) {
    const v = viajePorId(o.viaje1_id);
    return `
        <button type="button" data-ruta="${esc(o.viaje1_id)}"
                class="tarjeta-ruta text-left rounded-xl border-2 border-green-500 bg-green-50 hover:border-green-600 p-4 flex flex-col gap-2">
            ${etiquetaExacta(o.prioridad == 1, 'Recomendado · mismas sucursales')}
            ${tramoHtml(v)}
        </button>`;
}

function tarjetaTrasbordo(o) {
    const a = viajePorId(o.viaje1_id), b = viajePorId(o.viaje2_id);
    return `
        <button type="button" data-ruta="${esc(o.viaje1_id)}-${esc(o.viaje2_id)}"
                class="tarjeta-ruta text-left rounded-xl border-2 border-sky-400 bg-sky-50 hover:border-sky-600 p-4 flex flex-col gap-3">
            <div class="flex items-center justify-between gap-2">
                ${etiquetaExacta(o.prioridad == 3, 'Recomendado con trasbordo')}
                <span class="text-xs font-semibold text-on-surface">Llega aprox. ${esc(fechaHora(o.llegada_final))}</span>
            </div>
            ${tramoHtml(a, 'Tramo 1 · ')}
            <div class="flex items-center gap-2 text-xs text-sky-800 bg-sky-100 rounded-lg px-3 py-1.5">
                <span class="material-symbols-outlined text-[18px]">sync_alt</span>
                Trasbordo en ${esc(a.destino)} · espera ${esc(esperaTexto(a.llegada_estimada, b.fecha_salida))}
            </div>
            ${tramoHtml(b, 'Tramo 2 · ')}
        </button>`;
}

// Todos los viajes programados, para que el que asigna elija otro si quiere (como directo).
function pintarOtros() {
    const filtro = $('filtro-otros').value.trim().toLowerCase();
    const lista = estado.rutas.viajes.filter(v => !filtro ||
        `${v.viaje_id} ${v.origen} ${v.destino}`.toLowerCase().includes(filtro));
    $('cuenta-otros').textContent = `(${estado.rutas.viajes.length})`;
    // CAMBIAR: cuántos viajes muestra la lista "Otros viajes" (60).
    $('lista-otros').innerHTML = lista.slice(0, 60).map(v => {
        const sinLugar = Number(v.libre) < peso();
        return `
            <button type="button" data-ruta="${esc(v.viaje_id)}" ${sinLugar ? 'disabled' : ''}
                    class="tarjeta-ruta text-left rounded-xl border-2 p-4 ${sinLugar ? 'border-surface-container bg-surface-container opacity-60 cursor-not-allowed' : 'border-surface-container bg-white hover:border-primary/60'}">
                ${sinLugar ? '<span class="text-xs font-semibold text-on-surface-variant">Sin lugar para este paquete</span>' : ''}
                ${tramoHtml(v)}
            </button>`;
    }).join('') + (lista.length > 60 ? `<p class="text-xs text-on-surface-variant">y ${lista.length - 60} más: usá el filtro.</p>` : '');
    marcarSeleccion();
}
$('filtro-otros').addEventListener('input', pintarOtros);

// Elegir una ruta (uno o dos viajes), "crear nuevo" o "más tarde".
document.addEventListener('click', e => {
    const tarjeta = e.target.closest('.tarjeta-ruta');
    const opcion = e.target.closest('[data-opcion-viaje]');
    if (tarjeta && !tarjeta.disabled) {
        estado.viaje = { modo: 'existente', ids: tarjeta.dataset.ruta.split('-').map(Number) };
    } else if (opcion) {
        estado.viaje = { modo: opcion.dataset.opcionViaje, ids: [] };
    } else {
        return;
    }
    marcarSeleccion();
});

function marcarSeleccion() {
    const v = estado.viaje;
    const clave = claveRuta(v.ids);
    document.querySelectorAll('.tarjeta-ruta').forEach(t => {
        t.classList.add('ring-primary/60');
        t.classList.toggle('ring-4', v.modo === 'existente' && t.dataset.ruta === clave);
    });
    document.querySelectorAll('.opcion-viaje').forEach(o => {
        o.classList.add('ring-primary/60');
        o.classList.toggle('ring-4', o.dataset.opcionViaje === v.modo);
    });
    $('form-viaje-nuevo').hidden = v.modo !== 'nuevo';
}

// Viaje nuevo: al elegir la salida se muestra la llegada estimada y se piden vehículos y choferes libres.
$('nv-salida').addEventListener('change', async () => {
    const salida = $('nv-salida').value;
    const fecha = salida.slice(0, 10);
    const vSel = $('nv-vehiculo'), cSel = $('nv-chofer');
    vSel.length = 1; cSel.length = 1;
    vSel.disabled = cSel.disabled = true;
    $('nv-llegada').textContent = salida ? `Llegada estimada: ${fechaHora(llegadaEstimada(salida))}` : '';
    if (!fecha) return;
    try {
        estado.recursos = await api(`../../api/viajes.php?recursos=1&fecha=${fecha}`);
    } catch (err) { mostrarError(err); return; }
    const aptos = estado.recursos.vehiculos.filter(v => Number(v.capacidad) >= peso());
    vSel.options[0].text = aptos.length ? 'Elegí el vehículo' : 'No hay vehículos libres con capacidad';
    aptos.forEach(v => vSel.add(new Option(v.nombre, v.cod)));
    vSel.disabled = !aptos.length;
    cSel.options[0].text = 'Primero elegí el vehículo';
});

// Si el vehículo pide licencia especial, solo se ofrecen choferes con licencia C o E.
$('nv-vehiculo').addEventListener('change', () => {
    const cSel = $('nv-chofer');
    cSel.length = 1;
    const vehiculo = estado.recursos.vehiculos.find(v => v.cod === $('nv-vehiculo').value);
    if (!vehiculo) { cSel.disabled = true; return; }
    const especial = vehiculo.requiere_especial == 1;
    const choferes = estado.recursos.choferes.filter(c => !especial || ['C', 'E'].includes(c.licencia));
    cSel.options[0].text = choferes.length ? 'Elegí el chofer' : 'No hay choferes libres habilitados';
    choferes.forEach(c => cSel.add(new Option(c.nombre, c.cod)));
    cSel.disabled = !choferes.length;
    $('nv-pista-chofer').textContent = especial ? 'Este vehículo pide licencia C o E: se muestran solo esos choferes.' : '';
});

// ------------------------------------------------------------ paso 4: confirmar
function textoTramo(v) {
    return `Viaje ${v.viaje_id}: ${v.origen} → ${v.destino} · sale ${fechaHora(v.fecha_salida)} · ` +
           `llega aprox. ${fechaHora(v.llegada_estimada)} · ${v.tipo_veh_nombre} ${v.vehiculo_patente} · ${v.chofer_nombre}`;
}

function armarResumen() {
    const v = estado.viaje;
    const filasViaje = [];
    if (v.modo === 'existente') {
        const tramos = v.ids.map(viajePorId);
        if (tramos.length === 2) {
            filasViaje.push(['Tramo 1', textoTramo(tramos[0])]);
            filasViaje.push(['Trasbordo', `En ${tramos[0].destino}`]);
            filasViaje.push(['Tramo 2', textoTramo(tramos[1])]);
        } else {
            filasViaje.push(['Viaje', textoTramo(tramos[0])]);
        }
        filasViaje.push(['Llegada estimada a destino', fechaHora(tramos[tramos.length - 1].llegada_estimada)]);
    } else if (v.modo === 'nuevo') {
        const salida = $('nv-salida').value.replace('T', ' ');
        filasViaje.push(['Viaje nuevo', `${textoOpcion('nv-origen')} → ${textoOpcion('nv-destino')} · sale ${fechaHora(salida)} · ${textoOpcion('nv-vehiculo')} · ${textoOpcion('nv-chofer')}`]);
        filasViaje.push(['Llegada estimada a destino', fechaHora(llegadaEstimada(salida))]);
    } else {
        filasViaje.push(['Viaje', 'Sin viaje por ahora (queda pendiente de asignar)']);
    }
    const d = estado.direccion;
    const filas = [
        ['Remitente', nombreCliente(estado.remitente)],
        ['Destinatario', nombreCliente(estado.destinatario)],
        ['Dirección de entrega', `${d.calle_y_numero}, ${d.localidad_nombre}`],
        ['Paquete', `${kg(peso())} · ${textoOpcion('input-tipo')}`],
        ['Sucursales', `${textoOpcion('input-suc-orig')} → ${textoOpcion('input-suc-dest')}`],
        ['Fecha del envío', new Date($('input-fecha').value + 'T00:00').toLocaleDateString('es-AR')],
        ...filasViaje,
    ];
    $('resumen').innerHTML = filas.map(([k, val]) => `
        <div class="flex flex-col border-b border-surface-container pb-2">
            <dt class="text-xs text-on-surface-variant">${esc(k)}</dt>
            <dd class="text-on-surface">${esc(val)}</dd>
        </div>`).join('');
    $('error-confirmar').hidden = true;
}

$('btn-confirmar').addEventListener('click', async () => {
    const boton = $('btn-confirmar');
    const v = estado.viaje;
    const cuerpo = {
        fecha_envio: $('input-fecha').value,
        peso_kg: $('input-peso').value,
        tipo_cont_cod: $('input-tipo').value,
        remitente_dni: estado.remitente.cliente_dni,
        destinatario_dni: estado.destinatario.cliente_dni,
        sucursal_orig_cod: $('input-suc-orig').value,
        sucursal_dest_cod: $('input-suc-dest').value,
        direccion_entrega_id: estado.direccion.direccion_id,
        viaje_modo: v.modo,
    };
    if (v.modo === 'existente') cuerpo.viajes = v.ids;
    if (v.modo === 'nuevo') {
        cuerpo.viaje_nuevo = {
            fecha_salida: $('nv-salida').value,
            sucursal_origen_cod: $('nv-origen').value,
            sucursal_destino_cod: $('nv-destino').value,
            vehiculo_patente: $('nv-vehiculo').value,
            chofer_legajo: $('nv-chofer').value,
        };
    }

    boton.disabled = true;
    boton.textContent = 'Guardando…';
    try {
        const r = await api('../../api/asistente_envio.php', 'POST', cuerpo);
        document.querySelectorAll('[data-paso], #pasos').forEach(s => { s.hidden = true; });
        $('resultado-titulo').textContent = r.mensaje;
        $('link-historial').href = `../historial_estado/historial_estado.php?nro_envio=${r.nro_envio}`;
        $('link-viaje').hidden = !r.viajes.length;
        $('link-viaje').href = `../viaje_envio/viaje_envio.php?nro_envio=${r.nro_envio}`;
        $('resultado').hidden = false;
    } catch (err) {
        $('error-confirmar').textContent = err.message;
        $('error-confirmar').hidden = false;
    } finally {
        boton.disabled = false;
        boton.textContent = 'Crear envío';
    }
});

mostrarPaso(1);