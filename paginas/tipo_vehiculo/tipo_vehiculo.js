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

const API = 'http://localhost/logitrack/api/tipo_vehiculo.php';

function listarTipos() {
    fetch(API)
        .then(res => res.json())
        .then(tipos => {
            const tabla = document.getElementById('tabla-tipos');
            tabla.innerHTML = '';
            tipos.forEach(t => {
                tabla.innerHTML += `
                    <tr>
                        <td>${t.tipo_veh_cod}</td>
                        <td>${t.tipo_veh_nombre}</td>
                        <td>${t.tipo_veh_capacidad_kg}</td>
                        <td>${t.tipo_veh_requiere_lic_especial == 1 ? 'Sí' : 'No'}</td>
                        <td>
                            <button onclick="editarTipo('${t.tipo_veh_cod}', '${t.tipo_veh_nombre}', ${t.tipo_veh_capacidad_kg}, ${t.tipo_veh_requiere_lic_especial})">Editar</button>
                            <button onclick="eliminarTipo('${t.tipo_veh_cod}')">Eliminar</button>
                        </td>
                    </tr>
                `;
            });
        });
}

function crearTipo() {
    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            tipo_veh_cod:                document.getElementById('input-cod').value,
            tipo_veh_nombre:             document.getElementById('input-nombre').value,
            tipo_veh_capacidad_kg:       document.getElementById('input-capacidad').value,
            tipo_veh_requiere_lic_especial: document.getElementById('input-licencia').value
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarTipos();
    });
}

function editarTipo(cod, nombre, capacidad, licencia) {
    const nuevoNombre    = prompt('Nombre:', nombre);
    if (nuevoNombre === null) return;
    const nuevaCapacidad = prompt('Capacidad kg:', capacidad);
    if (nuevaCapacidad === null) return;
    const nuevaLicencia  = prompt('Requiere licencia especial (0 = No, 1 = Sí):', licencia);
    if (nuevaLicencia === null) return;

    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            tipo_veh_cod:                cod,
            tipo_veh_nombre:             nuevoNombre,
            tipo_veh_capacidad_kg:       nuevaCapacidad,
            tipo_veh_requiere_lic_especial: nuevaLicencia
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarTipos();
    });
}

function eliminarTipo(cod) {
    if (!confirm('¿Seguro que querés eliminar este tipo?')) return;
    fetch(API, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ tipo_veh_cod: cod })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarTipos();
    });
}

listarTipos();