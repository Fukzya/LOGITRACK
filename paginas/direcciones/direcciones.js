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

const API = 'http://localhost/logitrack/api/direcciones.php';
let paginaActual = 1;
let ordenActual  = 'direccion_id';
let dirActual    = 'ASC';

function listarDirecciones(pagina = 1) {
    const params = new URLSearchParams({ pagina: pagina, order_by: ordenActual, order_dir: dirActual });
    const filtroCalle       = document.getElementById('filtro-calle').value;
    const filtroClienteDni  = document.getElementById('filtro-cliente-dni').value;
    const filtroLocalidadCod = document.getElementById('filtro-localidad-cod').value;

    if (filtroCalle)        params.append('calle',        filtroCalle);
    if (filtroClienteDni)   params.append('cliente_dni',  filtroClienteDni);
    if (filtroLocalidadCod) params.append('localidad_cod', filtroLocalidadCod);

    const mostrarInactivos = document.getElementById('filtro-mostrar-inactivos');
    if (mostrarInactivos && mostrarInactivos.checked) {
        params.append('mostrar_inactivos', '1');
    }

    fetch(`${API}?${params}`)
        .then(res => res.json())
        .then(data => {
            paginaActual = pagina;
            const tabla = document.getElementById('tabla-direcciones');
            tabla.innerHTML = '';
            data.datos.forEach(d => {
                const btnEditar = d.activo == 1
                    ? `<button onclick="editarDireccion(${d.direccion_id}, '${d.calle_y_numero}', ${d.localidad_cod}, ${d.cliente_dni})">Editar</button>`
                    : '';
                const btnBaja = d.activo == 1
                    ? `<button onclick="eliminarDireccion(${d.direccion_id})">Dar de baja</button>`
                    : '';
                const btnReactivar = d.activo == 0 && esAdmin
                    ? `<button onclick="reactivarDireccion(${d.direccion_id})">Reactivar</button>`
                    : '';
                tabla.innerHTML += `
                    <tr>
                        <td>${d.direccion_id}</td>
                        <td>${d.calle_y_numero}</td>
                        <td>${d.localidad_cod}</td>
                        <td>${d.cliente_dni}</td>
                        <td>${d.activo == 1 ?
                            '<span style="color:green">Activo</span>' :
                            '<span style="color:red">Inactivo</span>'
                        }</td>
                        <td>${btnEditar} ${btnBaja} ${btnReactivar}</td>
                    </tr>
                `;
            });

            document.getElementById('paginacion').innerHTML = `
                <button onclick="listarDirecciones(${pagina - 1})" ${pagina === 1 ? 'disabled' : ''}>← Anterior</button>
                Página ${pagina} de ${data.total_paginas}
                <button onclick="listarDirecciones(${pagina + 1})" ${pagina === data.total_paginas ? 'disabled' : ''}>Siguiente →</button>
            `;
        });
}

function reactivarDireccion(id) {
    if (!confirm('¿Querés reactivar esta dirección?')) return;
    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ direccion_id: id, reactivar: true })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarDirecciones(paginaActual);
    });
}

function crearDireccion() {
    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            calle_y_numero: document.getElementById('input-calle').value,
            localidad_cod:  document.getElementById('input-localidad').value,
            cliente_dni:    document.getElementById('input-dni').value
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarDirecciones(paginaActual);
    });
}

function editarDireccion(id, calle, localidad, dni) {
    const nuevaCalle     = prompt('Calle y número:', calle);
    if (nuevaCalle === null) return;
    const nuevaLocalidad = prompt('Código de localidad:', localidad);
    if (nuevaLocalidad === null) return;
    const nuevoDni       = prompt('DNI del cliente:', dni);
    if (nuevoDni === null) return;

    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            direccion_id:   id,
            calle_y_numero: nuevaCalle,
            localidad_cod:  nuevaLocalidad,
            cliente_dni:    nuevoDni
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarDirecciones(paginaActual);
    });
}

function eliminarDireccion(id) {
    if (!confirm('¿Seguro que querés eliminar esta dirección?')) return;
    fetch(API, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ direccion_id: id })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarDirecciones(paginaActual);
    });
}

function ordenarPor(columna) {
    if (ordenActual === columna) {
        dirActual = dirActual === 'ASC' ? 'DESC' : 'ASC';
    } else {
        ordenActual = columna;
        dirActual   = 'DESC';
    }
    listarDirecciones(1);
}

document.getElementById('btn-filtrar').addEventListener('click', () => listarDirecciones(1));
document.getElementById('btn-limpiar').addEventListener('click', () => {
    document.getElementById('filtro-calle').value        = '';
    document.getElementById('filtro-cliente-dni').value  = '';
    document.getElementById('filtro-localidad-cod').value = '';
    const chkInactivos = document.getElementById('filtro-mostrar-inactivos');
    if (chkInactivos) chkInactivos.checked = false;
    listarDirecciones(1);
});

document.getElementById('th-direccion-id').addEventListener('click', () => ordenarPor('direccion_id'));
document.getElementById('th-calle').addEventListener('click', () => ordenarPor('calle_y_numero'));
document.getElementById('th-localidad-cod').addEventListener('click', () => ordenarPor('localidad_cod'));
document.getElementById('th-cliente-dni').addEventListener('click', () => ordenarPor('cliente_dni'));

listarDirecciones();