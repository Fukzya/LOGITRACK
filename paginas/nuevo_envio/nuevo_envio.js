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
    viaje: { modo: null, id: null, datos: null },
    viajes: [],          // los que se muestran en el paso 3
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
    for (const id of ['input-suc-orig', 'input-suc-dest']) ss.forEach(s => $(id).add(new Option(s.nombre, s.cod)));
}).catch(mostrarError);

const hoy = new Date();
const isoLocal = d => new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString();
$('input-fecha').value = isoLocal(hoy).slice(0, 10);
$('nv-salida').min = isoLocal(hoy).slice(0, 16);

// ------------------------------------------------------------ formato
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
}
$('input-suc-orig').addEventListener('change', () => { sugerida.orig = false; $('pista-orig').textContent = ''; });
$('input-suc-dest').addEventListener('change', () => { sugerida.dest = false; $('pista-dest').textContent = ''; });

// ------------------------------------------------------------ paso 3: viaje
const peso = () => Number($('input-peso').value);

async function cargarViajes() {
    const caja = $('lista-viajes');
    caja.innerHTML = '<p class="text-sm text-on-surface-variant">Buscando viajes…</p>';
    try {
        estado.viajes = await api(`../../api/viajes.php?disponibles=1&sucursal_dest=${encodeURIComponent($('input-suc-dest').value)}`);
    } catch (err) { mostrarError(err); return; }

    // Si el viaje elegido antes ya no está o no tiene lugar con el peso nuevo, se desmarca.
    const previo = estado.viajes.find(v => String(v.viaje_id) === String(estado.viaje.id));
    if (estado.viaje.modo === 'existente' && (!previo || Number(previo.libre) < peso())) {
        estado.viaje = { modo: null, id: null, datos: null };
    }

    const provDestino = provincias.get(String(sucursales.find(s => s.cod === $('input-suc-dest').value)?.provincia_cod)) || '';
    if (!estado.viajes.length) {
        caja.innerHTML = '<p class="text-sm text-on-surface-variant md:col-span-2">No hay viajes programados: creá uno nuevo.</p>';
    } else {
        caja.innerHTML = estado.viajes.map(v => tarjetaViaje(v, provDestino)).join('');
    }
    marcarSeleccion();
}

function tarjetaViaje(v, provDestino) {
    const sinLugar = Number(v.libre) < peso();
    const recomendado = v.recomendado == 1;
    const pct = Math.min(100, Math.round(Number(v.carga) / Number(v.capacidad) * 100));
    const ruta = Number(v.cantidad_envios) > 0
        ? `<div><span class="text-on-surface-variant">Desde:</span> ${esc(v.origenes)}</div>
           <div><span class="text-on-surface-variant">Hacia:</span> ${esc(v.destinos)}</div>`
        : '<div class="text-on-surface-variant italic">Sin envíos todavía: ruta libre</div>';
    const borde = sinLugar ? 'border-surface-container bg-surface-container opacity-60 cursor-not-allowed'
                : recomendado ? 'border-green-500 bg-green-50 hover:border-green-600'
                : 'border-surface-container bg-white hover:border-primary/60';
    return `
        <button type="button" data-viaje="${esc(v.viaje_id)}" ${sinLugar ? 'disabled' : ''}
                class="tarjeta-viaje text-left rounded-xl border-2 p-4 flex flex-col gap-2 transition-colors ${borde}">
            <div class="flex items-center justify-between gap-2">
                <span class="text-sm font-bold text-on-surface">Viaje ${esc(v.viaje_id)}</span>
                ${recomendado ? `<span class="text-xs font-semibold text-green-800 bg-green-200 rounded-full px-2 py-0.5">Recomendado · va a ${esc(provDestino)}</span>` : ''}
                ${sinLugar ? '<span class="text-xs font-semibold text-on-surface-variant bg-surface-container-highest rounded-full px-2 py-0.5">Sin lugar</span>' : ''}
            </div>
            <div class="flex items-center gap-1 text-sm text-on-surface"><span class="material-symbols-outlined text-[18px] text-primary">event</span>Sale ${esc(fechaHora(v.fecha_salida))}</div>
            <div class="text-xs flex flex-col gap-0.5">${ruta}</div>
            <div class="text-xs text-on-surface-variant">${esc(v.tipo_veh_nombre)} ${esc(v.vehiculo_patente)} · ${esc(v.chofer_nombre)} · ${esc(v.cantidad_envios)} envío(s)</div>
            <div class="flex flex-col gap-1">
                <div class="h-1.5 rounded-full bg-surface-container-high overflow-hidden"><div class="h-full ${pct > 85 ? 'bg-red-500' : 'bg-primary'}" style="width:${Math.max(pct, 2)}%"></div></div>
                <span class="text-xs text-on-surface-variant">Quedan ${esc(kg(v.libre))} de ${esc(kg(v.capacidad))}</span>
            </div>
        </button>`;
}

// Elegir un viaje existente, "crear nuevo" o "más tarde".
document.addEventListener('click', e => {
    const tarjeta = e.target.closest('.tarjeta-viaje');
    const opcion = e.target.closest('[data-opcion-viaje]');
    if (tarjeta && !tarjeta.disabled) {
        const v = estado.viajes.find(x => String(x.viaje_id) === tarjeta.dataset.viaje);
        estado.viaje = { modo: 'existente', id: v.viaje_id, datos: v };
    } else if (opcion) {
        estado.viaje = { modo: opcion.dataset.opcionViaje, id: null, datos: null };
    } else {
        return;
    }
    marcarSeleccion();
});

function marcarSeleccion() {
    const v = estado.viaje;
    document.querySelectorAll('.tarjeta-viaje').forEach(t =>
        t.classList.toggle('ring-4', v.modo === 'existente' && t.dataset.viaje === String(v.id)));
    document.querySelectorAll('.tarjeta-viaje, .opcion-viaje').forEach(t => t.classList.add('ring-primary/50'));
    document.querySelectorAll('.opcion-viaje').forEach(o =>
        o.classList.toggle('ring-4', o.dataset.opcionViaje === v.modo));
    $('form-viaje-nuevo').hidden = v.modo !== 'nuevo';
}

// Viaje nuevo: al elegir la salida se piden los vehículos y choferes libres ese día.
$('nv-salida').addEventListener('change', async () => {
    const fecha = $('nv-salida').value.slice(0, 10);
    const vSel = $('nv-vehiculo'), cSel = $('nv-chofer');
    vSel.length = 1; cSel.length = 1;
    vSel.disabled = cSel.disabled = true;
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
function armarResumen() {
    const v = estado.viaje;
    const textoViaje = v.modo === 'existente'
        ? `Viaje ${v.id}: sale ${fechaHora(v.datos.fecha_salida)} · ${v.datos.tipo_veh_nombre} ${v.datos.vehiculo_patente} · ${v.datos.chofer_nombre}`
        : v.modo === 'nuevo'
            ? `Viaje nuevo: sale ${fechaHora($('nv-salida').value.replace('T', ' '))} · ${textoOpcion('nv-vehiculo')} · ${textoOpcion('nv-chofer')}`
            : 'Sin viaje por ahora (queda pendiente de asignar)';
    const d = estado.direccion;
    const filas = [
        ['Remitente', nombreCliente(estado.remitente)],
        ['Destinatario', nombreCliente(estado.destinatario)],
        ['Dirección de entrega', `${d.calle_y_numero}, ${d.localidad_nombre}`],
        ['Paquete', `${kg(peso())} · ${textoOpcion('input-tipo')}`],
        ['Sucursales', `${textoOpcion('input-suc-orig')} → ${textoOpcion('input-suc-dest')}`],
        ['Fecha del envío', new Date($('input-fecha').value + 'T00:00').toLocaleDateString('es-AR')],
        ['Viaje', textoViaje],
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
    if (v.modo === 'existente') cuerpo.viaje_id = v.id;
    if (v.modo === 'nuevo') {
        cuerpo.viaje_nuevo = {
            fecha_salida: $('nv-salida').value,
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
        $('link-viaje').hidden = !r.viaje_id;
        $('link-viaje').href = `../viaje_envio/viaje_envio.php?viaje_id=${r.viaje_id}`;
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
