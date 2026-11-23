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

const API = 'http://localhost/logitrack/api/sucursales.php';
let paginaActual = 1;
let ordenActual  = 'sucursal_cod';
let dirActual    = 'ASC';

function listarSucursales(pagina = 1) {
    const params = new URLSearchParams({ pagina: pagina, order_by: ordenActual, order_dir: dirActual });
    const filtroNombre = document.getElementById('filtro-nombre').value;

    if (filtroNombre) params.append('nombre', filtroNombre);

    const mostrarInactivos = document.getElementById('filtro-mostrar-inactivos');
    if (mostrarInactivos && mostrarInactivos.checked) {
        params.append('mostrar_inactivos', '1');
    }

    fetch(`${API}?${params}`)
        .then(res => res.json())
        .then(data => {
            paginaActual = pagina;
            const tabla = document.getElementById('tabla-sucursales');
            tabla.innerHTML = '';
            data.datos.forEach(s => {
                const btnEditar = s.activo == 1
                    ? `<button onclick="editarSucursal('${s.sucursal_cod}', '${s.sucursal_nombre}', ${s.sucursal_localidad_cod}, '${s.direccion}')">Editar</button>`
                    : '';
                const btnBaja = s.activo == 1
                    ? `<button onclick="eliminarSucursal('${s.sucursal_cod}')">Dar de baja</button>`
                    : '';
                const btnReactivar = s.activo == 0 && esAdmin
                    ? `<button onclick="reactivarSucursal('${s.sucursal_cod}')">Reactivar</button>`
                    : '';
                tabla.innerHTML += `
                    <tr>
                        <td>${s.sucursal_cod}</td>
                        <td>${s.sucursal_nombre}</td>
                        <td>${s.sucursal_localidad_cod}</td>
                        <td>${s.direccion}</td>
                        <td>${s.activo == 1 ?
                            '<span style="color:green">Activo</span>' :
                            '<span style="color:red">Inactivo</span>'
                        }</td>
                        <td>${btnEditar} ${btnBaja} ${btnReactivar}</td>
                    </tr>
                `;
            });

            document.getElementById('paginacion').innerHTML = `
                <button onclick="listarSucursales(${pagina - 1})" ${pagina === 1 ? 'disabled' : ''}>← Anterior</button>
                Página ${pagina} de ${data.total_paginas}
                <button onclick="listarSucursales(${pagina + 1})" ${pagina === data.total_paginas ? 'disabled' : ''}>Siguiente →</button>
            `;
        });
}

function ordenarPor(columna) {
    if (ordenActual === columna) {
        dirActual = dirActual === 'ASC' ? 'DESC' : 'ASC';
    } else {
        ordenActual = columna;
        dirActual   = 'DESC';
    }
    listarSucursales(1);
}

function reactivarSucursal(cod) {
    if (!confirm('¿Querés reactivar esta sucursal?')) return;
    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sucursal_cod: cod, reactivar: true })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarSucursales(paginaActual);
    });
}

function crearSucursal() {
    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            sucursal_cod:           document.getElementById('input-cod').value,
            sucursal_nombre:        document.getElementById('input-nombre').value,
            sucursal_localidad_cod: document.getElementById('input-localidad').value,
            direccion:              document.getElementById('input-direccion').value
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarSucursales(paginaActual);
    });
}

function editarSucursal(cod, nombre, localidad, direccion) {
    const nuevoNombre    = prompt('Nombre:', nombre);
    if (nuevoNombre === null) return;
    const nuevaLocalidad = prompt('Código de localidad:', localidad);
    if (nuevaLocalidad === null) return;
    const nuevaDireccion = prompt('Dirección:', direccion);
    if (nuevaDireccion === null) return;

    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            sucursal_cod:           cod,
            sucursal_nombre:        nuevoNombre,
            sucursal_localidad_cod: nuevaLocalidad,
            direccion:              nuevaDireccion
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarSucursales(paginaActual);
    });
}

function eliminarSucursal(cod) {
    if (!confirm('¿Seguro que querés dar de baja esta sucursal?\nEsta acción la marcará como inactiva.')) return;
    fetch(API, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sucursal_cod: cod })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarSucursales(paginaActual);
    });
}

document.getElementById('btn-filtrar').addEventListener('click', () => listarSucursales(1));
document.getElementById('btn-limpiar').addEventListener('click', () => {
    document.getElementById('filtro-nombre').value = '';
    const chkInactivos = document.getElementById('filtro-mostrar-inactivos');
    if (chkInactivos) chkInactivos.checked = false;
    listarSucursales(1);
});

document.getElementById('th-sucursal-cod').addEventListener('click', () => ordenarPor('sucursal_cod'));
document.getElementById('th-sucursal-nombre').addEventListener('click', () => ordenarPor('sucursal_nombre'));
document.getElementById('th-localidad').addEventListener('click', () => ordenarPor('sucursal_localidad_cod'));

listarSucursales();
