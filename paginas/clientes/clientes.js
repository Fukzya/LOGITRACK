fetch('../../api/check_session.php')
  .then(res => res.json())
  .then(data => {
    if (!data.autenticado) {
      window.location.href = '../../login.html';
    }
  });

function cerrarSesion(e) {
  e.preventDefault();
  fetch('../../api/logout.php')
    .then(res => res.json())
    .then(() => { window.location.href = '../../login.html'; });
}

const API = 'http://localhost/logitrack/api/clientes.php';
let paginaActual = 1;
let ordenActual  = 'cliente_dni';
let dirActual    = 'ASC';

function listarClientes(pagina = 1) {
    const params = new URLSearchParams({ pagina: pagina, order_by: ordenActual, order_dir: dirActual });
    const filtroDni      = document.getElementById('filtro-dni').value;
    const filtroNombre   = document.getElementById('filtro-nombre').value;
    const filtroApellido = document.getElementById('filtro-apellido').value;

    if (filtroDni)      params.append('dni',      filtroDni);
    if (filtroNombre)   params.append('nombre',   filtroNombre);
    if (filtroApellido) params.append('apellido', filtroApellido);

    const mostrarInactivos = document.getElementById('filtro-mostrar-inactivos');
    if (mostrarInactivos && mostrarInactivos.checked) {
        params.append('mostrar_inactivos', '1');
    }

    fetch(`${API}?${params}`)
        .then(res => res.json())
        .then(data => {
            paginaActual = pagina;
            const tabla = document.getElementById('tabla-clientes');
            tabla.innerHTML = '';
            data.datos.forEach(c => {
                tabla.innerHTML += `
                    <tr>
                        <td>${c.cliente_dni}</td>
                        <td>${c.nombre}</td>
                        <td>${c.apellido}</td>
                        <td>${c.mail}</td>
                        <td>${c.telefono}</td>
                        <td>${c.localidad_cod}</td>
                        <td>${c.activo == 1 ?
                            '<span style="color:green">Activo</span>' :
                            '<span style="color:red">Inactivo</span>'
                        }</td>
                        <td>
                            ${c.activo == 1 ? `<button onclick="editarCliente(${c.cliente_dni}, '${c.nombre}', '${c.apellido}', '${c.mail}', '${c.telefono}', ${c.localidad_cod})">Editar</button>` : ''}
                            ${c.activo == 1 ? `<button onclick="eliminarCliente(${c.cliente_dni})">Eliminar</button>` : ''}
                            ${c.activo == 0 && esAdmin ? `<button onclick="reactivarCliente(${c.cliente_dni})">Reactivar</button>` : ''}
                        </td>
                    </tr>
                `;
            });

            document.getElementById('paginacion').innerHTML = `
                <button onclick="listarClientes(${pagina - 1})" ${pagina === 1 ? 'disabled' : ''}>← Anterior</button>
                Página ${pagina} de ${data.total_paginas}
                <button onclick="listarClientes(${pagina + 1})" ${pagina === data.total_paginas ? 'disabled' : ''}>Siguiente →</button>
            `;
        });
}

function reactivarCliente(dni) {
    if (!confirm('¿Querés reactivar este cliente?')) return;
    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cliente_dni: dni, reactivar: true })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarClientes(paginaActual);
    });
}

function ordenarPor(columna) {
    if (ordenActual === columna) {
        dirActual = dirActual === 'ASC' ? 'DESC' : 'ASC';
    } else {
        ordenActual = columna;
        dirActual   = 'DESC';
    }
    listarClientes(1);
}

function crearCliente() {
    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            cliente_dni:   document.getElementById('input-dni').value,
            nombre:        document.getElementById('input-nombre').value,
            apellido:      document.getElementById('input-apellido').value,
            mail:          document.getElementById('input-mail').value,
            telefono:      document.getElementById('input-telefono').value,
            localidad_cod: document.getElementById('input-localidad').value
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarClientes(paginaActual);
    });
}

function editarCliente(dni, nombre, apellido, mail, telefono, localidad) {
    const nuevoNombre    = prompt('Nombre:', nombre);
    if (nuevoNombre === null) return;
    const nuevoApellido  = prompt('Apellido:', apellido);
    if (nuevoApellido === null) return;
    const nuevoMail      = prompt('Mail:', mail);
    if (nuevoMail === null) return;
    const nuevoTelefono  = prompt('Teléfono:', telefono);
    if (nuevoTelefono === null) return;
    const nuevaLocalidad = prompt('Código de localidad:', localidad);
    if (nuevaLocalidad === null) return;

    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            cliente_dni:   dni,
            nombre:        nuevoNombre,
            apellido:      nuevoApellido,
            mail:          nuevoMail,
            telefono:      nuevoTelefono,
            localidad_cod: nuevaLocalidad
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarClientes(paginaActual);
    });
}

function eliminarCliente(dni) {
    if (!confirm('¿Seguro que querés eliminar este cliente?\nEsta acción lo marcará como inactivo.')) return;
    fetch(API, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cliente_dni: dni })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarClientes(paginaActual);
    });
}

document.getElementById('btn-filtrar').addEventListener('click', () => listarClientes(1));
document.getElementById('btn-limpiar').addEventListener('click', () => {
    document.getElementById('filtro-dni').value      = '';
    document.getElementById('filtro-nombre').value   = '';
    document.getElementById('filtro-apellido').value = '';
    const chkInactivos = document.getElementById('filtro-mostrar-inactivos');
    if (chkInactivos) chkInactivos.checked = false;
    listarClientes(1);
});

document.getElementById('th-cliente-dni').addEventListener('click', () => ordenarPor('cliente_dni'));
document.getElementById('th-nombre').addEventListener('click', () => ordenarPor('nombre'));
document.getElementById('th-apellido').addEventListener('click', () => ordenarPor('apellido'));

listarClientes();
