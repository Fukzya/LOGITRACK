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

const API = 'http://localhost/logitrack/api/historial_estado.php';
let paginaActual = 1;
let ordenActual  = 'id_historial';
let dirActual    = 'DESC';

function listarHistorial(pagina = 1) {
    const params = new URLSearchParams({ pagina: pagina, order_by: ordenActual, order_dir: dirActual });
    const nroEnvio   = document.getElementById('filtro-nro-envio').value;
    const estado     = document.getElementById('filtro-estado').value;
    const fechaDesde = document.getElementById('filtro-fecha-desde').value;
    const fechaHasta = document.getElementById('filtro-fecha-hasta').value;
    const sucursal   = document.getElementById('filtro-sucursal').value;

    if (nroEnvio)   params.append('nro_envio',    nroEnvio);
    if (estado)     params.append('estado',        estado);
    if (fechaDesde) params.append('fecha_desde',   fechaDesde);
    if (fechaHasta) params.append('fecha_hasta',   fechaHasta);
    if (sucursal)   params.append('sucursal_cod',  sucursal);

    fetch(`${API}?${params}`)
        .then(res => res.json())
        .then(data => {
            paginaActual = pagina;
            const tabla = document.getElementById('tabla-historial');
            tabla.innerHTML = '';
            data.datos.forEach(h => {
                tabla.innerHTML += `
                    <tr>
                        <td>${h.id_historial}</td>
                        <td>${h.nro_envio}</td>
                        <td>${h.sucursal_cod}</td>
                        <td>${h.estado}</td>
                        <td>${h.fecha_hora}</td>
                        <td>${h.observacion ?? ''}</td>
                        <td>
                            <button onclick="eliminarHistorial(${h.id_historial})">Eliminar</button>
                        </td>
                    </tr>
                `;
            });

            document.getElementById('paginacion').innerHTML = `
                <button onclick="listarHistorial(${pagina - 1})" ${pagina === 1 ? 'disabled' : ''}>← Anterior</button>
                Página ${pagina} de ${data.total_paginas}
                <button onclick="listarHistorial(${pagina + 1})" ${pagina === data.total_paginas ? 'disabled' : ''}>Siguiente →</button>
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
    listarHistorial(1);
}

function cargarSucursales() {
    fetch('http://localhost/logitrack/api/sucursales.php')
        .then(res => res.json())
        .then(sucursales => {
            const sel = document.getElementById('filtro-sucursal');
            sucursales.forEach(s => {
                const opt = document.createElement('option');
                opt.value       = s.sucursal_cod;
                opt.textContent = s.sucursal_cod;
                sel.appendChild(opt);
            });
        });
}

function crearHistorial() {
    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            nro_envio:    document.getElementById('input-envio').value,
            sucursal_cod: document.getElementById('input-sucursal').value,
            estado:       document.getElementById('input-estado').value,
            fecha_hora:   document.getElementById('input-fecha').value,
            observacion:  document.getElementById('input-observacion').value || null
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarHistorial(paginaActual);
    });
}

function eliminarHistorial(id) {
    if (!confirm('¿Seguro que querés eliminar este registro?')) return;
    fetch(API, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_historial: id })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarHistorial(paginaActual);
    });
}

document.getElementById('btn-filtrar').addEventListener('click', () => listarHistorial(1));
document.getElementById('btn-limpiar').addEventListener('click', () => {
    document.getElementById('filtro-nro-envio').value  = '';
    document.getElementById('filtro-estado').value     = '';
    document.getElementById('filtro-fecha-desde').value = '';
    document.getElementById('filtro-fecha-hasta').value = '';
    document.getElementById('filtro-sucursal').value   = '';
    listarHistorial(1);
});

document.getElementById('th-id-historial').addEventListener('click', () => ordenarPor('id_historial'));
document.getElementById('th-nro-envio').addEventListener('click', () => ordenarPor('nro_envio'));
document.getElementById('th-fecha-hora').addEventListener('click', () => ordenarPor('fecha_hora'));

cargarSucursales();
listarHistorial();
