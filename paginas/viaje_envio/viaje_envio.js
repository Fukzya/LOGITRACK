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

const API = 'http://localhost/logitrack/api/viaje_envio.php';
let paginaActual = 1;
let ordenActual  = 'viaje_id';
let dirActual    = 'ASC';

function listarViajeEnvio(pagina = 1) {
    const params = new URLSearchParams({ pagina: pagina, order_by: ordenActual, order_dir: dirActual });
    const filtroViajeId  = document.getElementById('filtro-viaje-id').value;
    const filtroNroEnvio = document.getElementById('filtro-nro-envio').value;

    if (filtroViajeId)  params.append('viaje_id',  filtroViajeId);
    if (filtroNroEnvio) params.append('nro_envio', filtroNroEnvio);

    fetch(`${API}?${params}`)
        .then(res => res.json())
        .then(data => {
            paginaActual = pagina;
            const tabla = document.getElementById('tabla-viaje-envio');
            tabla.innerHTML = '';
            data.datos.forEach(ve => {
                tabla.innerHTML += `
                    <tr>
                        <td>${ve.viaje_id}</td>
                        <td>${ve.nro_envio}</td>
                        <td>
                            <button onclick="eliminarViajeEnvio(${ve.viaje_id}, ${ve.nro_envio})">Desasignar</button>
                        </td>
                    </tr>
                `;
            });

            document.getElementById('paginacion').innerHTML = `
                <button onclick="listarViajeEnvio(${pagina - 1})" ${pagina === 1 ? 'disabled' : ''}>← Anterior</button>
                Página ${pagina} de ${data.total_paginas}
                <button onclick="listarViajeEnvio(${pagina + 1})" ${pagina === data.total_paginas ? 'disabled' : ''}>Siguiente →</button>
            `;
        });
}

function crearViajeEnvio() {
    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            viaje_id:  document.getElementById('input-viaje').value,
            nro_envio: document.getElementById('input-envio').value
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarViajeEnvio(paginaActual);
    });
}

function eliminarViajeEnvio(viaje, envio) {
    if (!confirm('¿Seguro que querés desasignar este envío del viaje?')) return;
    fetch(API, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ viaje_id: viaje, nro_envio: envio })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarViajeEnvio(paginaActual);
    });
}

function ordenarPor(columna) {
    if (ordenActual === columna) {
        dirActual = dirActual === 'ASC' ? 'DESC' : 'ASC';
    } else {
        ordenActual = columna;
        dirActual   = 'ASC';
    }
    listarViajeEnvio(1);
}

document.getElementById('btn-filtrar').addEventListener('click', () => listarViajeEnvio(1));
document.getElementById('btn-limpiar').addEventListener('click', () => {
    document.getElementById('filtro-viaje-id').value  = '';
    document.getElementById('filtro-nro-envio').value = '';
    listarViajeEnvio(1);
});

document.getElementById('th-viaje-id').addEventListener('click', () => ordenarPor('viaje_id'));
document.getElementById('th-nro-envio').addEventListener('click', () => ordenarPor('nro_envio'));

listarViajeEnvio();