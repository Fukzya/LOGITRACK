// ==============================================================================================
// QUÉ ES:  el "motor" de todas las pantallas de listado + formulario (Clientes, Envíos, Viajes...):
//          listar, filtrar, ordenar, paginar, guardar, editar, dar de baja. Cada pantalla solo
//          le pasa su configuración (ver el .js de cada carpeta).
// PARA CAMBIAR: textos y botones comunes a todas las pantallas -> "CAMBIAR:" abajo.
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
/*
 * Componente reutilizable de pantalla CRUD (capa Vista).
 * Cada pantalla llama a pantallaCrud({...}) describiendo su API, columnas, campos y filtros,
 * y este archivo resuelve lo que es igual en todas: listar, filtrar, ordenar, paginar,
 * alta y edición con el mismo formulario, baja (lógica o física) y reactivación.
 *
 * Configuración:
 *   api            URL de la API de la entidad
 *   clave          campo que identifica la fila (ej. 'cliente_dni')
 *   nombre         cómo se llama una fila en los mensajes (ej. 'cliente')
 *   tabla          id del <tbody>;  paginacion: id del div de paginación (opcional)
 *   columnas       [fila => html]  (cada celda; los datos se pasan por esc())
 *   form, titulo   ids del <form> y del título del formulario
 *   tituloAlta     texto del título en modo alta;  tituloEdicion: fila => texto
 *   campos         { 'id-del-input': 'propiedad' }  (inputs del formulario)
 *   filtros        { 'id-del-filtro': 'parametro' } (inputs o checkboxes)
 *   orden          { col, dir } inicial;  encabezados: { 'id-del-th': 'columna' }
 *   baja           'logica' (activo = 0, se puede reactivar), 'fisica' (DELETE) o null (sin baja)
 *   editable       true/false (default true)
 *   accionesExtra  fila => html de botones adicionales (con data-accion)
 *   acciones       { nombreAccion: fila => ... } para esos botones
 *   antesDeGuardar datos => datos (opcional, para ajustar el cuerpo que se envía)
 *   entidad        nombre en la tabla de permisos (config/permisos.php): sin permiso de alta/editar
 *                  se oculta el formulario; sin editar/baja/reactivar, esos botones
 */
function pantallaCrud(cfg) {
    const $ = id => document.getElementById(id);
    const baja = cfg.baja === undefined ? 'logica' : cfg.baja;
    const editable = cfg.editable !== false;
    const permite = accion => !cfg.entidad || puede(cfg.entidad, accion);
    let orden = { ...(cfg.orden || {}) };
    let pagina = 1;
    let filas = new Map();       // clave -> fila, para precargar el formulario
    let editando = null;         // null = alta; si no, la fila que se edita
    // Sin edición, sin baja y sin acciones propias, la tabla no lleva columna "Acciones".
    const conAcciones = editable || baja !== null || !!cfg.accionesExtra;

    // Los filtros se pueden precargar desde la URL: pantalla.php?nro_envio=123
    const desdeUrl = new URLSearchParams(location.search);
    for (const [id, param] of Object.entries(cfg.filtros || {})) {
        if (!desdeUrl.has(param) || !$(id)) continue;
        if ($(id).type === 'checkbox') $(id).checked = desdeUrl.get(param) === '1';
        else $(id).value = desdeUrl.get(param);
    }

    // ------------------------------------------------------------ listado
    // CAMBIAR: texto cuando no hay filas ("No hay registros con esos filtros.") y el de la
    //          paginación ("Página X de Y"): están en esta función.
    async function listar(p = 1) {
        const params = new URLSearchParams({ pagina: p });
        if (orden.col) { params.set('order_by', orden.col); params.set('order_dir', orden.dir || 'ASC'); }
        for (const [id, param] of Object.entries(cfg.filtros || {})) {
            const el = $(id);
            if (!el) continue;
            const valor = el.type === 'checkbox' ? (el.checked ? '1' : '') : el.value.trim();
            if (valor !== '') params.set(param, valor);
        }
        try {
            const data = await api(`${cfg.api}?${params}`);
            pagina = data.pagina;
            filas = new Map(data.datos.map(f => [String(f[cfg.clave]), f]));
            const columnas = cfg.columnas.length + (conAcciones ? 1 : 0);
            $(cfg.tabla).innerHTML = data.datos.map(f => `
                <tr>${cfg.columnas.map(c => `<td>${c(f)}</td>`).join('')}${conAcciones ? `<td>${botones(f)}</td>` : ''}</tr>`
            ).join('') || `<tr><td colspan="${columnas}">No hay registros con esos filtros.</td></tr>`;

            if (cfg.paginacion && $(cfg.paginacion)) {
                $(cfg.paginacion).innerHTML = `
                    <button data-pagina="${data.pagina - 1}" ${data.pagina <= 1 ? 'disabled' : ''}>← Anterior</button>
                    Página ${data.pagina} de ${data.total_paginas} (${data.total} registros)
                    <button data-pagina="${data.pagina + 1}" ${data.pagina >= data.total_paginas ? 'disabled' : ''}>Siguiente →</button>`;
            }
        } catch (err) {
            mostrarError(err);
        }
    }

    // CAMBIAR: los botones de cada fila (Editar, Dar de baja, Eliminar, Reactivar) y el
    //          texto "Solo consulta" cuando el rol no puede hacer nada.
    function botones(f) {
        const k = esc(f[cfg.clave]);
        const activo = baja !== 'logica' || f.activo == 1;
        let html = '';
        if (activo && editable && permite('editar')) html += `<button data-accion="editar" data-clave="${k}">Editar</button> `;
        if (activo && baja === 'logica' && permite('baja')) html += `<button data-accion="baja" data-clave="${k}">Dar de baja</button> `;
        if (baja === 'fisica' && permite('baja')) html += `<button data-accion="eliminar" data-clave="${k}">Eliminar</button> `;
        if (!activo && permite('reactivar') && typeof esAdmin !== 'undefined' && esAdmin) html += `<button data-accion="reactivar" data-clave="${k}">Reactivar</button> `;
        if (cfg.accionesExtra) html += cfg.accionesExtra(f);
        return html || '<span class="text-on-surface-variant">Solo consulta</span>';
    }

    // ------------------------------------------------------------ formulario
    function modoAlta() {
        editando = null;
        if (!cfg.form) return;
        $(cfg.form).reset();
        for (const id of Object.keys(cfg.campos)) $(id).disabled = false;
        $(cfg.titulo).textContent = cfg.tituloAlta;
        $(cfg.form).querySelector('[type=submit]').textContent = 'Agregar';
        const cancelar = $(cfg.form).querySelector('.btn-cancelar');
        if (cancelar) cancelar.hidden = true;
    }

    function modoEdicion(f) {
        editando = f;
        for (const [id, prop] of Object.entries(cfg.campos)) {
            const el = $(id);
            let valor = f[prop] ?? '';
            if (el.type === 'checkbox') el.checked = valor == 1;
            else if (el.type === 'datetime-local') el.value = String(valor).replace(' ', 'T').slice(0, 16);
            else el.value = valor;
            el.disabled = prop === cfg.clave;   // la clave primaria no se edita
        }
        $(cfg.titulo).textContent = cfg.tituloEdicion(f);
        $(cfg.form).querySelector('[type=submit]').textContent = 'Guardar cambios';
        const cancelar = $(cfg.form).querySelector('.btn-cancelar');
        if (cancelar) cancelar.hidden = false;
        $(cfg.form).scrollIntoView({ behavior: 'smooth' });
    }

    async function guardar(e) {
        e.preventDefault();   // el navegador ya validó required, min/max, pattern, etc.
        let datos = {};
        for (const [id, prop] of Object.entries(cfg.campos)) {
            const el = $(id);
            datos[prop] = el.type === 'checkbox' ? el.checked : el.value;
        }
        if (editando) datos[cfg.clave] = editando[cfg.clave];
        if (cfg.antesDeGuardar) datos = cfg.antesDeGuardar(datos, editando);
        const eraEdicion = editando !== null;
        try {
            const r = await api(cfg.api, eraEdicion ? 'PUT' : 'POST', datos);
            alert(r.mensaje);
            modoAlta();
            listar(eraEdicion ? pagina : 1);
        } catch (err) {
            mostrarError(err);
        }
    }

    // ------------------------------------------------------------ acciones de cada fila
    // CAMBIAR: las preguntas de confirmación ("¿Dar de baja...?") están en el switch de abajo.
    async function accionSimple(metodo, cuerpo, pregunta) {
        if (!confirm(pregunta)) return;
        try {
            alert((await api(cfg.api, metodo, cuerpo)).mensaje);
            listar(pagina);
        } catch (err) {
            mostrarError(err);
        }
    }

    $(cfg.tabla).addEventListener('click', e => {
        const b = e.target.closest('button[data-accion]');
        if (!b) return;
        const f = filas.get(b.dataset.clave);
        const k = f[cfg.clave];
        const cuerpo = { [cfg.clave]: k };
        switch (b.dataset.accion) {
            case 'editar':    modoEdicion(f); break;
            case 'baja':      accionSimple('DELETE', cuerpo, `¿Dar de baja ${cfg.nombre} ${k}?\nNo se borra: queda inactivo y se puede reactivar.`); break;
            case 'eliminar':  accionSimple('DELETE', cuerpo, `¿Eliminar ${cfg.nombre} ${k}? Esta acción no se puede deshacer.`); break;
            case 'reactivar': accionSimple('PUT', { ...cuerpo, reactivar: true }, `¿Reactivar ${cfg.nombre} ${k}?`); break;
            default:
                if (cfg.acciones && cfg.acciones[b.dataset.accion]) cfg.acciones[b.dataset.accion](f, { listar: () => listar(pagina) });
        }
    });

    if (cfg.paginacion && $(cfg.paginacion)) {
        $(cfg.paginacion).addEventListener('click', e => {
            const b = e.target.closest('button[data-pagina]');
            if (b && !b.disabled) listar(Number(b.dataset.pagina));
        });
    }

    for (const [id, col] of Object.entries(cfg.encabezados || {})) {
        $(id).style.cursor = 'pointer';
        $(id).title = 'Ordenar';
        $(id).addEventListener('click', () => {
            orden = { col, dir: orden.col === col && orden.dir === 'ASC' ? 'DESC' : 'ASC' };
            listar(1);
        });
    }

    // Sin permiso de alta ni de edición, el formulario no se muestra (pantalla de solo consulta).
    if (cfg.form && !permite('alta') && !permite('editar')) {
        ($(cfg.form).closest('.rounded-xl') || $(cfg.form)).hidden = true;
    }
    if (cfg.form) {
        $(cfg.form).addEventListener('submit', guardar);
        const cancelar = $(cfg.form).querySelector('.btn-cancelar');
        if (cancelar) cancelar.addEventListener('click', modoAlta);
    }
    const filtrar = $('btn-filtrar');
    if (filtrar) filtrar.addEventListener('click', () => listar(1));
    const limpiar = $('btn-limpiar');
    if (limpiar) limpiar.addEventListener('click', () => {
        for (const id of Object.keys(cfg.filtros || {})) {
            const el = $(id);
            if (el) { if (el.type === 'checkbox') el.checked = false; else el.value = ''; }
        }
        listar(1);
    });

    listar(1);
    return { listar: () => listar(pagina) };
}

/** Celda de estado para las entidades con baja lógica. */
function celdaEstado(f) {
    return f.activo == 1 ? '<span style="color:green">Activo</span>' : '<span style="color:red">Inactivo</span>';
}
