fetch('../../api/check_session.php')
  .then(res => res.json())
  .then(data => {
    if (!data.autenticado) {
      window.location.href = '../../login.html';
    } else if (data.rol !== 'admin') {
      alert('Acceso solo para administradores');
      window.location.href = '../../index.php';
    }
  });

function cerrarSesion(e) {
  e.preventDefault();
  fetch('../../api/logout.php')
    .then(res => res.json())
    .then(() => { window.location.href = '../../login.html'; });
}

const API = 'http://localhost/logitrack/api/usuarios.php';

function listarUsuarios() {
    fetch(API)
        .then(res => res.json())
        .then(usuarios => {
            const tabla = document.getElementById('tabla-usuarios');
            tabla.innerHTML = '';
            usuarios.forEach(u => {
                const estado    = u.activo == 1 ? 'Activo' : 'Inactivo';
                const btnToggle = u.activo == 1
                    ? `<button onclick="toggleActivo(${u.usuario_id}, 1)">Desactivar</button>`
                    : `<button onclick="toggleActivo(${u.usuario_id}, 0)">Activar</button>`;
                tabla.innerHTML += `
                    <tr>
                        <td>${u.usuario_id}</td>
                        <td>${u.username}</td>
                        <td>${u.rol}</td>
                        <td>${estado}</td>
                        <td>
                            <button onclick="editarRol(${u.usuario_id}, '${u.rol}')">Editar rol</button>
                            <button onclick="cambiarPassword(${u.usuario_id})">Cambiar password</button>
                            ${btnToggle}
                        </td>
                    </tr>
                `;
            });
        });
}

function crearUsuario() {
    const username = document.getElementById('input-username').value;
    const password = document.getElementById('input-password').value;
    const rol      = document.getElementById('input-rol').value;
    const msgDiv   = document.getElementById('mensaje-error');

    if (!username || !password) {
        msgDiv.textContent = 'El usuario y la contraseña son obligatorios';
        return;
    }

    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password, rol })
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            msgDiv.textContent = data.error;
        } else {
            msgDiv.textContent = '';
            document.getElementById('input-username').value = '';
            document.getElementById('input-password').value = '';
            document.getElementById('input-rol').value = 'operador';
            listarUsuarios();
        }
    });
}

function editarRol(id, rolActual) {
    const nuevoRol = prompt('Rol (admin / operador):', rolActual);
    if (nuevoRol === null) return;

    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ usuario_id: id, rol: nuevoRol })
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
        } else {
            listarUsuarios();
        }
    });
}

function cambiarPassword(id) {
    const nuevaPass = prompt('Nueva contraseña:');
    if (nuevaPass === null) return;
    if (nuevaPass === '') {
        alert('La contraseña no puede estar vacía');
        return;
    }

    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ usuario_id: id, password: nuevaPass })
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
        } else {
            alert(data.mensaje);
        }
    });
}

function toggleActivo(id, estadoActual) {
    const accion = estadoActual === 1 ? 'desactivar' : 'activar';
    if (!confirm(`¿Seguro que querés ${accion} este usuario?`)) return;

    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ usuario_id: id, activo: estadoActual === 1 ? 0 : 1 })
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
        } else {
            listarUsuarios();
        }
    });
}

listarUsuarios();
