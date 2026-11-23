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

const API = 'http://localhost/logitrack/api/viajes.php';
let paginaActual = 1;
let ordenActual  = 'viaje_id';
let dirActual    = 'DESC';

function listarViajes(pagina = 1) {
    const params = new URLSearchParams({ pagina: pagina, order_by: ordenActual, order_dir: dirActual });
    const fechaDesde  = document.getElementById('filtro-fecha-desde').value;
    const fechaHasta  = document.getElementById('filtro-fecha-hasta').value;
    const chofer      = document.getElementById('filtro-chofer').value;
    const soloActivos = document.getElementById('filtro-solo-activos').checked ? '1' : '';

    if (fechaDesde)  params.append('fecha_desde',   fechaDesde);
    if (fechaHasta)  params.append('fecha_hasta',   fechaHasta);
    if (chofer)      params.append('chofer_legajo', chofer);
    if (soloActivos) params.append('solo_activos',  soloActivos);

    fetch(`${API}?${params}`)
        .then(res => res.json())
        .then(data => {
            paginaActual = pagina;
            const tabla = document.getElementById('tabla-viajes');
            tabla.innerHTML = '';
            data.datos.forEach(v => {
                tabla.innerHTML += `
                    <tr>
                        <td>${v.viaje_id}</td>
                        <td>${v.fecha_salida}</td>
                        <td>${v.fecha_llegada ?? 'En curso'}</td>
                        <td>${v.vehiculo_patente}</td>
                        <td>${v.chofer_legajo}</td>
                        <td>
                            <button onclick="editarViaje(${v.viaje_id}, '${v.fecha_salida}', '${v.fecha_llegada}', '${v.vehiculo_patente}', ${v.chofer_legajo})">Editar</button>
                            <button onclick="eliminarViaje(${v.viaje_id})">Eliminar</button>
                        </td>
                    </tr>
                `;
            });

            document.getElementById('paginacion').innerHTML = `
                <button onclick="listarViajes(${pagina - 1})" ${pagina === 1 ? 'disabled' : ''}>← Anterior</button>
                Página ${pagina} de ${data.total_paginas}
                <button onclick="listarViajes(${pagina + 1})" ${pagina === data.total_paginas ? 'disabled' : ''}>Siguiente →</button>
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
    listarViajes(1);
}

function cargarChoferes() {
    fetch('http://localhost/logitrack/api/choferes.php')
        .then(res => res.json())
        .then(choferes => {
            const sel = document.getElementById('filtro-chofer');
            choferes.forEach(c => {
                const opt = document.createElement('option');
                opt.value       = c.chofer_legajo;
                opt.textContent = c.nombre + ' ' + c.apellido;
                sel.appendChild(opt);
            });
        });
}

function crearViaje() {
    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            fecha_salida:     document.getElementById('input-salida').value,
            fecha_llegada:    document.getElementById('input-llegada').value || null,
            vehiculo_patente: document.getElementById('input-patente').value,
            chofer_legajo:    document.getElementById('input-chofer').value
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarViajes(paginaActual);
    });
}

function editarViaje(id, salida, llegada, patente, chofer) {
    const nuevaPatente = prompt('Patente:', patente);
    if (nuevaPatente === null) return;
    const nuevoChofer  = prompt('Legajo chofer:', chofer);
    if (nuevoChofer === null) return;
    const nuevaLlegada = prompt('Fecha llegada (YYYY-MM-DD HH:MM:SS):', llegada);
    if (nuevaLlegada === null) return;

    fetch(API, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            viaje_id:         id,
            fecha_salida:     salida,
            fecha_llegada:    nuevaLlegada || null,
            vehiculo_patente: nuevaPatente,
            chofer_legajo:    nuevoChofer
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarViajes(paginaActual);
    });
}

function eliminarViaje(id) {
    if (!confirm('¿Seguro que querés eliminar este viaje?')) return;
    fetch(API, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ viaje_id: id })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarViajes(paginaActual);
    });
}

document.getElementById('btn-filtrar').addEventListener('click', () => listarViajes(1));
document.getElementById('btn-limpiar').addEventListener('click', () => {
    document.getElementById('filtro-fecha-desde').value = '';
    document.getElementById('filtro-fecha-hasta').value = '';
    document.getElementById('filtro-chofer').value = '';
    document.getElementById('filtro-solo-activos').checked = false;
    listarViajes(1);
});

document.getElementById('th-viaje-id').addEventListener('click', () => ordenarPor('viaje_id'));
document.getElementById('th-fecha-salida').addEventListener('click', () => ordenarPor('fecha_salida'));
document.getElementById('th-fecha-llegada').addEventListener('click', () => ordenarPor('fecha_llegada'));

cargarChoferes();
listarViajes();
