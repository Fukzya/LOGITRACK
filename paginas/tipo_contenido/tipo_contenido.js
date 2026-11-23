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

const API = 'http://localhost/logitrack/api/tipo_contenido.php';

function listarTipos() {
    fetch(API)
        .then(res => res.json())
        .then(tipos => {
            const tabla = document.getElementById('tabla-tipos');
            tabla.innerHTML = '';
            tipos.forEach(t => {
                tabla.innerHTML += `
                    <tr>
                        <td>${t.tipo_cont_cod}</td>
                        <td>${t.tipo_cont_nombre}</td>
                        <td>${t.tipo_cont_requiere_seguro == 1 ? 'Sí' : 'No'}</td>
                        <td>
                            <button onclick="editarTipo('${t.tipo_cont_cod}', '${t.tipo_cont_nombre}', ${t.tipo_cont_requiere_seguro})">Editar</button>
                            <button onclick="eliminarTipo('${t.tipo_cont_cod}')">Eliminar</button>
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
            tipo_cont_cod:             document.getElementById('input-cod').value,
            tipo_cont_nombre:          document.getElementById('input-nombre').value,
            tipo_cont_requiere_seguro: document.getElementById('input-seguro').value
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarTipos();
    });
}

function editarTipo(cod, nombre, seguro) {
    const nuevoNombre = prompt('Nombre:', nombre);
    if (nuevoNombre === null) return;
    const nuevoSeguro = prompt('Requiere seguro (0 = No, 1 = Sí):', seguro);
    if (nuevoSeguro === null) return;

    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            tipo_cont_cod:             cod,
            tipo_cont_nombre:          nuevoNombre,
            tipo_cont_requiere_seguro: nuevoSeguro
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
        body: JSON.stringify({ tipo_cont_cod: cod })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarTipos();
    });
}

listarTipos();