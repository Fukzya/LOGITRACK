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

const API = 'http://localhost/logitrack/api/localidades.php';
let ordenActual = 'provincia_cod';
let dirActual   = 'ASC';

function listarLocalidades() {
    fetch(`${API}?order_by=${ordenActual}&order_dir=${dirActual}`)
        .then(res => res.json())
        .then(localidades => {
            const tabla = document.getElementById('tabla-localidades');
            tabla.innerHTML = '';
            localidades.forEach(l => {
                tabla.innerHTML += `
                    <tr>
                        <td>${l.localidad_cod}</td>
                        <td>${l.localidad_nombre}</td>
                        <td>${l.provincia_cod}</td>
                        <td>
                            <button onclick="editarLocalidad(${l.localidad_cod}, '${l.localidad_nombre}', ${l.provincia_cod})">Editar</button>
                            <button onclick="eliminarLocalidad(${l.localidad_cod})">Eliminar</button>
                        </td>
                    </tr>
                `;
            });
        });
}

function crearLocalidad() {
    const nombre = document.getElementById('input-nombre').value;
    const provincia = document.getElementById('input-provincia').value;
    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ localidad_nombre: nombre, provincia_cod: provincia })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        document.getElementById('input-nombre').value = '';
        document.getElementById('input-provincia').value = '';
        listarLocalidades();
    });
}

function editarLocalidad(cod, nombreActual, provinciaActual) {
    const nuevoNombre = prompt('Nuevo nombre:', nombreActual);
    if (nuevoNombre === null) return;
    const nuevaProvincia = prompt('Nuevo código de provincia:', provinciaActual);
    if (nuevaProvincia === null) return;
    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ localidad_cod: cod, localidad_nombre: nuevoNombre, provincia_cod: nuevaProvincia })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarLocalidades();
    });
}

function eliminarLocalidad(cod) {
    if (!confirm('¿Seguro que querés eliminar esta localidad?')) return;
    fetch(API, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ localidad_cod: cod })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarLocalidades();
    });
}

function ordenarPor(columna) {
    if (ordenActual === columna) {
        dirActual = dirActual === 'ASC' ? 'DESC' : 'ASC';
    } else {
        ordenActual = columna;
        dirActual   = 'ASC';
    }
    listarLocalidades();
}

document.getElementById('th-localidad-cod').addEventListener('click', () => ordenarPor('localidad_cod'));
document.getElementById('th-localidad-nombre').addEventListener('click', () => ordenarPor('localidad_nombre'));
document.getElementById('th-provincia-cod').addEventListener('click', () => ordenarPor('provincia_cod'));

listarLocalidades();