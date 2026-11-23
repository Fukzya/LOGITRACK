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

const API = 'http://localhost/logitrack/api/incidentes.php';
let paginaActual = 1;
let ordenActual  = 'fecha_hora';
let dirActual    = 'DESC';

function listarIncidentes(pagina = 1) {
    const params = new URLSearchParams({ pagina: pagina, order_by: ordenActual, order_dir: dirActual });
    const filtroViajeId    = document.getElementById('filtro-viaje-id').value;
    const filtroTipo       = document.getElementById('filtro-tipo').value;
    const filtroFechaDesde = document.getElementById('filtro-fecha-desde').value;
    const filtroFechaHasta = document.getElementById('filtro-fecha-hasta').value;

    if (filtroViajeId)    params.append('viaje_id',    filtroViajeId);
    if (filtroTipo)       params.append('tipo',        filtroTipo);
    if (filtroFechaDesde) params.append('fecha_desde', filtroFechaDesde);
    if (filtroFechaHasta) params.append('fecha_hasta', filtroFechaHasta);

    fetch(`${API}?${params}`)
        .then(res => res.json())
        .then(data => {
            paginaActual = pagina;
            const tabla = document.getElementById('tabla-incidentes');
            tabla.innerHTML = '';
            data.datos.forEach(i => {
                tabla.innerHTML += `
                    <tr>
                        <td>${i.incidente_id}</td>
                        <td>${i.viaje_id}</td>
                        <td>${i.fecha_hora}</td>
                        <td>${i.tipo_incidente}</td>
                        <td>${i.descripcion ?? ''}</td>
                        <td>
                            <button onclick="editarIncidente(${i.incidente_id}, ${i.viaje_id}, '${i.fecha_hora}', '${i.tipo_incidente}', '${i.descripcion ?? ''}')">Editar</button>
                            <button onclick="eliminarIncidente(${i.incidente_id})">Eliminar</button>
                        </td>
                    </tr>
                `;
            });

            document.getElementById('paginacion').innerHTML = `
                <button onclick="listarIncidentes(${pagina - 1})" ${pagina === 1 ? 'disabled' : ''}>← Anterior</button>
                Página ${pagina} de ${data.total_paginas}
                <button onclick="listarIncidentes(${pagina + 1})" ${pagina === data.total_paginas ? 'disabled' : ''}>Siguiente →</button>
            `;
        });
}

function crearIncidente() {
    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            viaje_id:       document.getElementById('input-viaje').value,
            fecha_hora:     document.getElementById('input-fecha').value,
            tipo_incidente: document.getElementById('input-tipo').value,
            descripcion:    document.getElementById('input-descripcion').value || null
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarIncidentes(paginaActual);
    });
}

function editarIncidente(id, viaje, fecha, tipo, descripcion) {
    const nuevoTipo        = prompt('Tipo:', tipo);
    if (nuevoTipo === null) return;
    const nuevaDescripcion = prompt('Descripción:', descripcion);
    if (nuevaDescripcion === null) return;

    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            incidente_id:   id,
            viaje_id:       viaje,
            fecha_hora:     fecha,
            tipo_incidente: nuevoTipo,
            descripcion:    nuevaDescripcion || null
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarIncidentes(paginaActual);
    });
}

function eliminarIncidente(id) {
    if (!confirm('¿Seguro que querés eliminar este incidente?')) return;
    fetch(API, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ incidente_id: id })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarIncidentes(paginaActual);
    });
}

function ordenarPor(columna) {
    if (ordenActual === columna) {
        dirActual = dirActual === 'ASC' ? 'DESC' : 'ASC';
    } else {
        ordenActual = columna;
        dirActual   = 'DESC';
    }
    listarIncidentes(1);
}

document.getElementById('btn-filtrar').addEventListener('click', () => listarIncidentes(1));
document.getElementById('btn-limpiar').addEventListener('click', () => {
    document.getElementById('filtro-viaje-id').value    = '';
    document.getElementById('filtro-tipo').value        = '';
    document.getElementById('filtro-fecha-desde').value = '';
    document.getElementById('filtro-fecha-hasta').value = '';
    listarIncidentes(1);
});

document.getElementById('th-incidente-id').addEventListener('click', () => ordenarPor('incidente_id'));
document.getElementById('th-viaje-id').addEventListener('click', () => ordenarPor('viaje_id'));
document.getElementById('th-fecha-hora').addEventListener('click', () => ordenarPor('fecha_hora'));
document.getElementById('th-tipo-incidente').addEventListener('click', () => ordenarPor('tipo_incidente'));

cargarCatalogo('input-tipo', 'incidentes');
cargarCatalogo('filtro-tipo', 'incidentes');
listarIncidentes();