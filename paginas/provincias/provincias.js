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

const API = 'http://localhost/logitrack/api/provincias.php';

function listarProvincias() {
    fetch(API)
        .then(res => res.json())
        .then(provincias => {
            const tabla = document.getElementById('tabla-provincias');
            tabla.innerHTML = '';
            provincias.forEach(p => {
                tabla.innerHTML += `
                    <tr>
                        <td>${p.provincia_cod}</td>
                        <td>${p.provincia_nombre}</td>
                        <td>
                            <button onclick="editarProvincia(${p.provincia_cod}, '${p.provincia_nombre}')">Editar</button>
                            <button onclick="eliminarProvincia(${p.provincia_cod})">Eliminar</button>
                        </td>
                    </tr>
                `;
            });
        });
}

function crearProvincia() {
    const nombre = document.getElementById('input-provincia').value;
    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ provincia_nombre: nombre })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        document.getElementById('input-provincia').value = '';
        listarProvincias();
    });
}

function editarProvincia(cod, nombreActual) {
    const nuevoNombre = prompt('Nuevo nombre:', nombreActual);
    if (nuevoNombre === null) return;
    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ provincia_cod: cod, provincia_nombre: nuevoNombre })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarProvincias();
    });
}

function eliminarProvincia(cod) {
    if (!confirm('¿Seguro que querés eliminar esta provincia?')) return;
    fetch(API, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ provincia_cod: cod })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarProvincias();
    });
}

listarProvincias();