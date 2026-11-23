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

const API = 'http://localhost/logitrack/api/vehiculos.php';
let paginaActual = 1;
let ordenActual  = 'vehiculo_patente';
let dirActual    = 'ASC';

function listarVehiculos(pagina = 1) {
    const params = new URLSearchParams({ pagina: pagina, order_by: ordenActual, order_dir: dirActual });
    const filtroPatente  = document.getElementById('filtro-patente').value;
    const filtroMarca    = document.getElementById('filtro-marca').value;
    const filtroTipoVeh  = document.getElementById('filtro-tipo-veh').value;

    if (filtroPatente)  params.append('patente',      filtroPatente);
    if (filtroMarca)    params.append('marca',        filtroMarca);
    if (filtroTipoVeh)  params.append('tipo_veh_cod', filtroTipoVeh);

    const mostrarInactivos = document.getElementById('filtro-mostrar-inactivos');
    if (mostrarInactivos && mostrarInactivos.checked) {
        params.append('mostrar_inactivos', '1');
    }

    fetch(`${API}?${params}`)
        .then(res => res.json())
        .then(data => {
            paginaActual = pagina;
            const tabla = document.getElementById('tabla-vehiculos');
            tabla.innerHTML = '';
            data.datos.forEach(v => {
                const btnEditar = v.activo == 1
                    ? `<button onclick="editarVehiculo('${v.vehiculo_patente}', '${v.marca}', '${v.modelo}', ${v.anio}, '${v.tipo_veh_cod}')">Editar</button>`
                    : '';
                const btnBaja = v.activo == 1
                    ? `<button onclick="eliminarVehiculo('${v.vehiculo_patente}')">Dar de baja</button>`
                    : '';
                const btnReactivar = v.activo == 0 && esAdmin
                    ? `<button onclick="reactivarVehiculo('${v.vehiculo_patente}')">Reactivar</button>`
                    : '';
                tabla.innerHTML += `
                    <tr>
                        <td>${v.vehiculo_patente}</td>
                        <td>${v.marca}</td>
                        <td>${v.modelo}</td>
                        <td>${v.anio}</td>
                        <td>${v.tipo_veh_cod}</td>
                        <td>${v.activo == 1 ?
                            '<span style="color:green">Activo</span>' :
                            '<span style="color:red">Inactivo</span>'
                        }</td>
                        <td>${btnEditar} ${btnBaja} ${btnReactivar}</td>
                    </tr>
                `;
            });

            document.getElementById('paginacion').innerHTML = `
                <button onclick="listarVehiculos(${pagina - 1})" ${pagina === 1 ? 'disabled' : ''}>← Anterior</button>
                Página ${pagina} de ${data.total_paginas}
                <button onclick="listarVehiculos(${pagina + 1})" ${pagina === data.total_paginas ? 'disabled' : ''}>Siguiente →</button>
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
    listarVehiculos(1);
}

function reactivarVehiculo(patente) {
    if (!confirm('¿Querés reactivar este vehículo?')) return;
    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ vehiculo_patente: patente, reactivar: true })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarVehiculos(paginaActual);
    });
}

function crearVehiculo() {
    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            vehiculo_patente: document.getElementById('input-patente').value,
            marca:            document.getElementById('input-marca').value,
            modelo:           document.getElementById('input-modelo').value,
            anio:             document.getElementById('input-anio').value,
            tipo_veh_cod:     document.getElementById('input-tipo').value
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarVehiculos(paginaActual);
    });
}

function editarVehiculo(patente, marca, modelo, anio, tipo) {
    const nuevaMarca  = prompt('Marca:', marca);
    if (nuevaMarca === null) return;
    const nuevoModelo = prompt('Modelo:', modelo);
    if (nuevoModelo === null) return;
    const nuevoAnio   = prompt('Año:', anio);
    if (nuevoAnio === null) return;
    const nuevoTipo   = prompt('Código tipo vehículo:', tipo);
    if (nuevoTipo === null) return;

    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            vehiculo_patente: patente,
            marca:            nuevaMarca,
            modelo:           nuevoModelo,
            anio:             nuevoAnio,
            tipo_veh_cod:     nuevoTipo
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarVehiculos(paginaActual);
    });
}

function eliminarVehiculo(patente) {
    if (!confirm('¿Seguro que querés dar de baja este vehículo?\nEsta acción lo marcará como inactivo.')) return;
    fetch(API, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ vehiculo_patente: patente })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarVehiculos(paginaActual);
    });
}

document.getElementById('btn-filtrar').addEventListener('click', () => listarVehiculos(1));
document.getElementById('btn-limpiar').addEventListener('click', () => {
    document.getElementById('filtro-patente').value  = '';
    document.getElementById('filtro-marca').value    = '';
    document.getElementById('filtro-tipo-veh').value = '';
    const chkInactivos = document.getElementById('filtro-mostrar-inactivos');
    if (chkInactivos) chkInactivos.checked = false;
    listarVehiculos(1);
});

document.getElementById('th-patente').addEventListener('click', () => ordenarPor('vehiculo_patente'));
document.getElementById('th-marca').addEventListener('click', () => ordenarPor('marca'));
document.getElementById('th-modelo').addEventListener('click', () => ordenarPor('modelo'));
document.getElementById('th-anio').addEventListener('click', () => ordenarPor('anio'));
document.getElementById('th-tipo-veh').addEventListener('click', () => ordenarPor('tipo_veh_cod'));

listarVehiculos();
