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

const API = 'http://localhost/logitrack/api/choferes.php';
let paginaActual = 1;
let ordenActual  = 'chofer_legajo';
let dirActual    = 'ASC';

function listarChoferes(pagina = 1) {
    const params = new URLSearchParams({ pagina: pagina, order_by: ordenActual, order_dir: dirActual });
    const filtroNombre       = document.getElementById('filtro-nombre').value;
    const filtroApellido     = document.getElementById('filtro-apellido').value;
    const filtroTipoLicencia = document.getElementById('filtro-tipo-licencia').value;

    if (filtroNombre)       params.append('nombre',        filtroNombre);
    if (filtroApellido)     params.append('apellido',      filtroApellido);
    if (filtroTipoLicencia) params.append('tipo_licencia', filtroTipoLicencia);

    const mostrarInactivos = document.getElementById('filtro-mostrar-inactivos');
    if (mostrarInactivos && mostrarInactivos.checked) {
        params.append('mostrar_inactivos', '1');
    }

    fetch(`${API}?${params}`)
        .then(res => res.json())
        .then(data => {
            paginaActual = pagina;
            const tabla = document.getElementById('tabla-choferes');
            tabla.innerHTML = '';
            data.datos.forEach(c => {
                const btnEditar = c.activo == 1
                    ? `<button onclick="editarChofer(${c.chofer_legajo}, '${c.nombre}', '${c.apellido}', '${c.chofer_tipo_licencia}')">Editar</button>`
                    : '';
                const btnBaja = c.activo == 1
                    ? `<button onclick="eliminarChofer(${c.chofer_legajo})">Dar de baja</button>`
                    : '';
                const btnReactivar = c.activo == 0 && esAdmin
                    ? `<button onclick="reactivarChofer(${c.chofer_legajo})">Reactivar</button>`
                    : '';
                tabla.innerHTML += `
                    <tr>
                        <td>${c.chofer_legajo}</td>
                        <td>${c.nombre}</td>
                        <td>${c.apellido}</td>
                        <td>${c.chofer_tipo_licencia}</td>
                        <td>${c.activo == 1 ?
                            '<span style="color:green">Activo</span>' :
                            '<span style="color:red">Inactivo</span>'
                        }</td>
                        <td>${btnEditar} ${btnBaja} ${btnReactivar}</td>
                    </tr>
                `;
            });

            document.getElementById('paginacion').innerHTML = `
                <button onclick="listarChoferes(${pagina - 1})" ${pagina === 1 ? 'disabled' : ''}>← Anterior</button>
                Página ${pagina} de ${data.total_paginas}
                <button onclick="listarChoferes(${pagina + 1})" ${pagina === data.total_paginas ? 'disabled' : ''}>Siguiente →</button>
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
    listarChoferes(1);
}

function reactivarChofer(legajo) {
    if (!confirm('¿Querés reactivar este chofer?')) return;
    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ chofer_legajo: legajo, reactivar: true })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarChoferes(paginaActual);
    });
}

function crearChofer() {
    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            nombre:               document.getElementById('input-nombre').value,
            apellido:             document.getElementById('input-apellido').value,
            chofer_tipo_licencia: document.getElementById('input-licencia').value
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarChoferes(paginaActual);
    });
}

function editarChofer(legajo, nombre, apellido, licencia) {
    const nuevoNombre   = prompt('Nombre:', nombre);
    if (nuevoNombre === null) return;
    const nuevoApellido = prompt('Apellido:', apellido);
    if (nuevoApellido === null) return;
    const nuevaLicencia = prompt('Tipo de licencia:', licencia);
    if (nuevaLicencia === null) return;

    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            chofer_legajo:        legajo,
            nombre:               nuevoNombre,
            apellido:             nuevoApellido,
            chofer_tipo_licencia: nuevaLicencia
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarChoferes(paginaActual);
    });
}

function eliminarChofer(legajo) {
    if (!confirm('¿Seguro que querés dar de baja este chofer?\nEsta acción lo marcará como inactivo.')) return;
    fetch(API, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ chofer_legajo: legajo })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarChoferes(paginaActual);
    });
}

document.getElementById('btn-filtrar').addEventListener('click', () => listarChoferes(1));
document.getElementById('btn-limpiar').addEventListener('click', () => {
    document.getElementById('filtro-nombre').value        = '';
    document.getElementById('filtro-apellido').value      = '';
    document.getElementById('filtro-tipo-licencia').value = '';
    const chkInactivos = document.getElementById('filtro-mostrar-inactivos');
    if (chkInactivos) chkInactivos.checked = false;
    listarChoferes(1);
});

document.getElementById('th-chofer-legajo').addEventListener('click', () => ordenarPor('chofer_legajo'));
document.getElementById('th-nombre').addEventListener('click', () => ordenarPor('nombre'));
document.getElementById('th-apellido').addEventListener('click', () => ordenarPor('apellido'));
document.getElementById('th-tipo-licencia').addEventListener('click', () => ordenarPor('chofer_tipo_licencia'));

listarChoferes();
