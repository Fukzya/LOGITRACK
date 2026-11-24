// Buscador de clientes reutilizable (capa Vista).
//
// En lugar de pedir un DNI "a ciegas", el usuario escribe parte del DNI, del nombre o del apellido,
// ve la lista con nombre + DNI + localidad y elige. Si el cliente no existe, lo da de alta ahí
// mismo (y, si hace falta, con su primera dirección) sin salir de la pantalla.
//
//   const remitente = selectorCliente(document.getElementById('sel-remitente'), {
//       conDireccion: false,              // true: el alta pide calle y número (obligatorio)
//       alElegir: (cliente, idDireccionNueva) => { ... },   // cliente = null al tocar "Cambiar"
//   });
//   remitente.valor        -> el cliente elegido (o null)
//   remitente.limpiar()

const CLASE_INPUT = 'w-full bg-[#fef3c7] px-3 py-2 rounded-lg text-sm border-0 focus:ring-1 focus:ring-primary outline-none text-on-surface';
const RESULTADOS_VISIBLES = 8;

// El catálogo de localidades se pide una sola vez aunque haya varios buscadores en la pantalla.
let promesaLocalidades = null;
function localidades() {
    promesaLocalidades ??= api('../../api/catalogos.php?tipo=localidades');
    return promesaLocalidades;
}

function formatoDni(dni) {
    return Number(dni).toLocaleString('es-AR');
}

function selectorCliente(contenedor, opciones = {}) {
    const conDireccion = !!opciones.conDireccion;
    const alElegir = opciones.alElegir || (() => {});
    let elegido = null;
    let temporizador = null;
    let numeroBusqueda = 0; // descarta respuestas viejas si el usuario sigue escribiendo

    contenedor.innerHTML = `
        <div data-sc="buscar" class="relative">
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[20px] pointer-events-none">search</span>
                <input data-sc="texto" type="search" autocomplete="off" placeholder="Escribí DNI, nombre o apellido"
                       class="${CLASE_INPUT} pl-10 py-2.5"/>
            </div>
            <ul data-sc="lista" class="absolute z-30 left-0 right-0 mt-1 bg-white rounded-lg shadow-lg border border-surface-container max-h-80 overflow-y-auto divide-y divide-surface-container" hidden></ul>
        </div>

        <div data-sc="elegido" hidden></div>

        <form data-sc="alta" class="mt-3 rounded-xl border border-dashed border-primary/50 bg-[#fffbf5] p-4 flex flex-col gap-3" hidden novalidate>
            <div class="flex items-center gap-2 text-sm font-semibold text-on-surface">
                <span class="material-symbols-outlined text-primary text-[20px]">person_add</span>Cliente nuevo
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <label class="flex flex-col gap-1 text-xs text-on-surface-variant">DNI
                    <input name="cliente_dni" type="number" min="1000000" max="99999999" required class="${CLASE_INPUT}"/></label>
                <label class="flex flex-col gap-1 text-xs text-on-surface-variant">Nombre
                    <input name="nombre" maxlength="80" required class="${CLASE_INPUT}"/></label>
                <label class="flex flex-col gap-1 text-xs text-on-surface-variant">Apellido
                    <input name="apellido" maxlength="80" required class="${CLASE_INPUT}"/></label>
                <label class="flex flex-col gap-1 text-xs text-on-surface-variant">Teléfono (opcional)
                    <input name="telefono" maxlength="30" pattern="\\+?[0-9 \\(\\)\\-]{6,30}" class="${CLASE_INPUT}"/></label>
                <label class="flex flex-col gap-1 text-xs text-on-surface-variant">Mail (opcional)
                    <input name="mail" type="email" maxlength="150" class="${CLASE_INPUT}"/></label>
                <label class="flex flex-col gap-1 text-xs text-on-surface-variant">Localidad
                    <select name="localidad_cod" required class="${CLASE_INPUT}"><option value="">Elegí la localidad</option></select></label>
                <label class="flex flex-col gap-1 text-xs text-on-surface-variant md:col-span-3">Calle y número ${conDireccion ? '(dirección de entrega)' : '(opcional)'}
                    <input name="calle_y_numero" maxlength="200" ${conDireccion ? 'required' : ''} class="${CLASE_INPUT}"/></label>
            </div>
            <p data-sc="error" class="text-sm text-red-700" hidden></p>
            <div class="flex gap-2">
                <button type="submit" class="bg-primary hover:bg-primary-container text-on-primary px-4 py-2 rounded-lg text-sm transition-colors">Guardar cliente</button>
                <button type="button" data-sc="cancelar-alta" class="bg-surface-container hover:bg-surface-container-high text-on-surface px-4 py-2 rounded-lg text-sm transition-colors">Cancelar</button>
            </div>
        </form>`;

    const parte = nombre => contenedor.querySelector(`[data-sc="${nombre}"]`);
    const texto = parte('texto'), lista = parte('lista'), form = parte('alta');

    // ------------------------------------------------------------ búsqueda
    texto.addEventListener('input', () => {
        clearTimeout(temporizador);
        temporizador = setTimeout(buscar, 250);
    });
    texto.addEventListener('focus', () => { if (lista.children.length) lista.hidden = false; });
    document.addEventListener('click', e => { if (!contenedor.contains(e.target)) lista.hidden = true; });

    async function buscar() {
        const t = texto.value.trim();
        if (t.length < 2) { lista.hidden = true; lista.innerHTML = ''; return; }
        const n = ++numeroBusqueda;
        let r;
        try {
            r = await api(`../../api/clientes.php?buscar=${encodeURIComponent(t)}&order_by=apellido`);
        } catch (err) { mostrarError(err); return; }
        if (n !== numeroBusqueda) return;

        const filas = r.datos.slice(0, RESULTADOS_VISIBLES).map(c => `
            <li><button type="button" data-dni="${esc(c.cliente_dni)}" class="w-full text-left px-4 py-2.5 hover:bg-[#fef3c7] focus:bg-[#fef3c7] outline-none">
                <div class="text-sm font-semibold text-on-surface">${esc(c.apellido)}, ${esc(c.nombre)}</div>
                <div class="text-xs text-on-surface-variant">DNI ${esc(formatoDni(c.cliente_dni))} · ${esc(c.localidad_nombre)}</div>
            </button></li>`);
        if (r.total > RESULTADOS_VISIBLES) {
            filas.push(`<li class="px-4 py-2 text-xs text-on-surface-variant">y ${r.total - RESULTADOS_VISIBLES} más: escribí algo más para achicar la lista.</li>`);
        }
        if (r.total === 0) {
            filas.push(`<li class="px-4 py-2.5 text-sm text-on-surface-variant">No hay clientes que coincidan con «${esc(t)}».</li>`);
        }
        filas.push(`<li><button type="button" data-sc="nuevo" class="w-full text-left px-4 py-2.5 text-sm font-semibold text-primary hover:bg-[#fef3c7] flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">person_add</span>¿No está? Dar de alta un cliente nuevo</button></li>`);

        lista.innerHTML = filas.join('');
        lista.hidden = false;
        lista._clientes = r.datos;
    }

    lista.addEventListener('click', e => {
        const boton = e.target.closest('button');
        if (!boton) return;
        if (boton.dataset.sc === 'nuevo') { abrirAlta(); return; }
        const c = lista._clientes.find(x => String(x.cliente_dni) === boton.dataset.dni);
        if (c) elegir(c);
    });

    // ------------------------------------------------------------ cliente elegido
    function elegir(c, idDireccionNueva = null) {
        elegido = c;
        lista.hidden = true;
        form.hidden = true;
        parte('buscar').hidden = true;
        const caja = parte('elegido');
        caja.innerHTML = `
            <div class="flex items-center justify-between gap-3 rounded-lg border border-primary/30 bg-[#fff7ed] px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary">check_circle</span>
                    <div>
                        <div class="text-sm font-semibold text-on-surface">${esc(c.nombre)} ${esc(c.apellido)}</div>
                        <div class="text-xs text-on-surface-variant">DNI ${esc(formatoDni(c.cliente_dni))} · ${esc(c.localidad_nombre)}</div>
                    </div>
                </div>
                <button type="button" data-sc="cambiar" class="text-sm text-primary hover:underline">Cambiar</button>
            </div>`;
        caja.hidden = false;
        caja.querySelector('[data-sc="cambiar"]').addEventListener('click', limpiar);
        alElegir(c, idDireccionNueva);
    }

    function limpiar() {
        elegido = null;
        parte('elegido').hidden = true;
        form.hidden = true;
        parte('buscar').hidden = false;
        texto.value = '';
        lista.innerHTML = '';
        lista.hidden = true;
        texto.focus();
        alElegir(null, null);
    }

    // ------------------------------------------------------------ alta en el momento
    async function abrirAlta() {
        lista.hidden = true;
        form.reset();
        parte('error').hidden = true;
        // Lo que ya escribió se aprovecha: si son números es el DNI, si no, nombre y apellido.
        const t = texto.value.trim();
        if (/^\d+$/.test(t)) {
            form.cliente_dni.value = t;
        } else if (t) {
            const [nombre, ...resto] = t.split(/\s+/);
            form.nombre.value = nombre;
            form.apellido.value = resto.join(' ');
        }
        form.hidden = false;
        const select = form.localidad_cod;
        if (select.options.length === 1) {
            try {
                (await localidades()).forEach(o => select.add(new Option(o.nombre, o.cod)));
            } catch (err) { mostrarError(err); }
        }
        (form.cliente_dni.value ? form.nombre : form.cliente_dni).focus();
    }

    parte('cancelar-alta').addEventListener('click', () => { form.hidden = true; texto.focus(); });

    form.addEventListener('submit', async e => {
        e.preventDefault();
        if (!form.reportValidity()) return;
        const datos = Object.fromEntries(new FormData(form));
        try {
            const r = await api('../../api/clientes.php', 'POST', datos);
            const nuevo = await api(`../../api/clientes.php?dni=${encodeURIComponent(r.cliente_dni)}`);
            elegir(nuevo.datos[0], r.direccion_id);
        } catch (err) {
            parte('error').textContent = err.message;
            parte('error').hidden = false;
        }
    });

    return {
        get valor() { return elegido; },
        elegir,
        limpiar,
    };
}
