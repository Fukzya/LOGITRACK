// Pantalla de clientes (capa Vista): muestra datos y manda las acciones a la API.
const API = '../../api/clientes.php';

let paginaActual = 1;
let ordenActual  = 'cliente_dni';
let dirActual    = 'ASC';
let clientesPagina = new Map();   // DNI -> datos del cliente, para precargar el formulario
let dniEditando    = null;        // null = alta; un DNI = edición

const $ = id => document.getElementById(id);

// ---------------------------------------------------------------- listado

async function listarClientes(pagina = 1) {
    const params = new URLSearchParams({ pagina, order_by: ordenActual, order_dir: dirActual });
    if ($('filtro-dni').value)      params.append('dni', $('filtro-dni').value);
    if ($('filtro-nombre').value)   params.append('nombre', $('filtro-nombre').value);
    if ($('filtro-apellido').value) params.append('apellido', $('filtro-apellido').value);
    const chkInactivos = $('filtro-mostrar-inactivos');
    if (chkInactivos && chkInactivos.checked) params.append('mostrar_inactivos', '1');

    try {
        const data = await api(`${API}?${params}`);
        paginaActual = data.pagina;
        clientesPagina = new Map(data.datos.map(c => [String(c.cliente_dni), c]));

        $('tabla-clientes').innerHTML = data.datos.map(c => {
            const activo = c.activo == 1;
            const acciones = activo
                ? `<button data-accion="editar" data-dni="${esc(c.cliente_dni)}">Editar</button>
                   <button data-accion="baja" data-dni="${esc(c.cliente_dni)}">Dar de baja</button>`
                : (esAdmin ? `<button data-accion="reactivar" data-dni="${esc(c.cliente_dni)}">Reactivar</button>` : '');
            return `
                <tr>
                    <td>${esc(c.cliente_dni)}</td>
                    <td>${esc(c.nombre)}</td>
                    <td>${esc(c.apellido)}</td>
                    <td>${esc(c.mail)}</td>
                    <td>${esc(c.telefono)}</td>
                    <td>${esc(c.localidad_nombre)}</td>
                    <td>${activo ? '<span style="color:green">Activo</span>' : '<span style="color:red">Inactivo</span>'}</td>
                    <td>${acciones}</td>
                </tr>`;
        }).join('') || '<tr><td colspan="8">No hay clientes con esos filtros.</td></tr>';

        $('paginacion').innerHTML = `
            <button onclick="listarClientes(${data.pagina - 1})" ${data.pagina <= 1 ? 'disabled' : ''}>← Anterior</button>
            Página ${data.pagina} de ${data.total_paginas} (${data.total} clientes)
            <button onclick="listarClientes(${data.pagina + 1})" ${data.pagina >= data.total_paginas ? 'disabled' : ''}>Siguiente →</button>`;
    } catch (err) {
        mostrarError(err);
    }
}

function ordenarPor(columna) {
    if (ordenActual === columna) {
        dirActual = dirActual === 'ASC' ? 'DESC' : 'ASC';
    } else {
        ordenActual = columna;
        dirActual   = 'ASC';
    }
    listarClientes(1);
}

// ---------------------------------------------------------------- formulario (alta y edición)

function modoAlta() {
    dniEditando = null;
    $('form-cliente').reset();
    $('input-dni').disabled = false;
    $('titulo-form').textContent = 'Nuevo cliente';
    $('btn-guardar').textContent = 'Agregar';
    $('btn-cancelar').hidden = true;
}

function modoEdicion(c) {
    dniEditando = c.cliente_dni;
    $('input-dni').value       = c.cliente_dni;
    $('input-dni').disabled    = true;          // la clave primaria no se edita
    $('input-nombre').value    = c.nombre;
    $('input-apellido').value  = c.apellido;
    $('input-mail').value      = c.mail ?? '';
    $('input-telefono').value  = c.telefono ?? '';
    $('input-localidad').value = c.localidad_cod;
    $('titulo-form').textContent = `Editando cliente ${c.cliente_dni}`;
    $('btn-guardar').textContent = 'Guardar cambios';
    $('btn-cancelar').hidden = false;
    $('form-cliente').scrollIntoView({ behavior: 'smooth' });
}

async function guardarCliente(e) {
    e.preventDefault();   // el navegador ya validó required, email, min/max y pattern
    const cliente = {
        cliente_dni:   dniEditando ?? $('input-dni').value,
        nombre:        $('input-nombre').value,
        apellido:      $('input-apellido').value,
        mail:          $('input-mail').value,
        telefono:      $('input-telefono').value,
        localidad_cod: $('input-localidad').value,
    };
    try {
        const data = await api(API, dniEditando === null ? 'POST' : 'PUT', cliente);
        alert(data.mensaje);
        modoAlta();
        listarClientes(paginaActual);
    } catch (err) {
        mostrarError(err);
    }
}

// ---------------------------------------------------------------- acciones de cada fila

async function darDeBaja(dni) {
    if (!confirm(`¿Dar de baja al cliente ${dni}?\nNo se borra: queda inactivo y se puede reactivar.`)) return;
    try {
        alert((await api(API, 'DELETE', { cliente_dni: dni })).mensaje);
        listarClientes(paginaActual);
    } catch (err) {
        mostrarError(err);
    }
}

async function reactivar(dni) {
    if (!confirm(`¿Reactivar al cliente ${dni}?`)) return;
    try {
        alert((await api(API, 'PUT', { cliente_dni: dni, reactivar: true })).mensaje);
        listarClientes(paginaActual);
    } catch (err) {
        mostrarError(err);
    }
}

// Un solo listener para todos los botones de la tabla: los datos van en data-*, no dentro del onclick.
$('tabla-clientes').addEventListener('click', e => {
    const boton = e.target.closest('button[data-accion]');
    if (!boton) return;
    const dni = boton.dataset.dni;
    if (boton.dataset.accion === 'editar')    modoEdicion(clientesPagina.get(dni));
    if (boton.dataset.accion === 'baja')      darDeBaja(dni);
    if (boton.dataset.accion === 'reactivar') reactivar(dni);
});

// ---------------------------------------------------------------- eventos

$('form-cliente').addEventListener('submit', guardarCliente);
$('btn-cancelar').addEventListener('click', modoAlta);
$('btn-filtrar').addEventListener('click', () => listarClientes(1));
$('btn-limpiar').addEventListener('click', () => {
    $('filtro-dni').value = '';
    $('filtro-nombre').value = '';
    $('filtro-apellido').value = '';
    const chk = $('filtro-mostrar-inactivos');
    if (chk) chk.checked = false;
    listarClientes(1);
});
$('th-cliente-dni').addEventListener('click', () => ordenarPor('cliente_dni'));
$('th-nombre').addEventListener('click', () => ordenarPor('nombre'));
$('th-apellido').addEventListener('click', () => ordenarPor('apellido'));
$('th-localidad').addEventListener('click', () => ordenarPor('localidad_nombre'));

cargarCatalogo('input-localidad', 'localidades');
listarClientes();
