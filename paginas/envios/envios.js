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

const API = 'http://localhost/logitrack/api/envios.php';
let paginaActual = 1;
let ordenActual  = 'nro_envio';
let dirActual    = 'DESC';

function listarEnvios(pagina = 1) {
    const params = new URLSearchParams({ pagina: pagina, order_by: ordenActual, order_dir: dirActual });
    const fechaDesde = document.getElementById('filtro-fecha-desde').value;
    const fechaHasta = document.getElementById('filtro-fecha-hasta').value;
    const sucOrig    = document.getElementById('filtro-sucursal-orig').value;
    const tipo       = document.getElementById('filtro-tipo').value;

    if (fechaDesde) params.append('fecha_desde', fechaDesde);
    if (fechaHasta) params.append('fecha_hasta', fechaHasta);
    if (sucOrig)    params.append('sucursal_orig', sucOrig);
    if (tipo)       params.append('tipo_cont', tipo);

    const mostrarInactivos = document.getElementById('filtro-mostrar-inactivos');
    if (mostrarInactivos && mostrarInactivos.checked) {
        params.append('mostrar_inactivos', '1');
    }

    fetch(`${API}?${params}`)
        .then(res => res.json())
        .then(data => {
            paginaActual = pagina;
            const tabla = document.getElementById('tabla-envios');
            tabla.innerHTML = '';
            data.datos.forEach(e => {
                tabla.innerHTML += `
                    <tr>
                        <td>${e.nro_envio}</td>
                        <td>${e.fecha_envio}</td>
                        <td>${e.peso_kg}</td>
                        <td>${e.tipo_cont_cod}</td>
                        <td>${e.remitente_dni}</td>
                        <td>${e.destinatario_dni}</td>
                        <td>${e.sucursal_orig_cod}</td>
                        <td>${e.sucursal_dest_cod}</td>
                        <td>${e.direccion_entrega_id}</td>
                        <td>${e.activo == 1 ?
                            '<span style="color:green">Activo</span>' :
                            '<span style="color:red">Inactivo</span>'
                        }</td>
                        <td>
                            <button onclick="eliminarEnvio(${e.nro_envio})">Eliminar</button>
                        </td>
                    </tr>
                `;
            });

            document.getElementById('paginacion').innerHTML = `
                <button onclick="listarEnvios(${pagina - 1})" ${pagina === 1 ? 'disabled' : ''}>← Anterior</button>
                Página ${pagina} de ${data.total_paginas}
                <button onclick="listarEnvios(${pagina + 1})" ${pagina === data.total_paginas ? 'disabled' : ''}>Siguiente →</button>
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
    listarEnvios(1);
}

function cargarSucursales() {
    fetch('http://localhost/logitrack/api/sucursales.php')
        .then(res => res.json())
        .then(sucursales => {
            const sel = document.getElementById('filtro-sucursal-orig');
            sucursales.forEach(s => {
                const opt = document.createElement('option');
                opt.value       = s.sucursal_cod;
                opt.textContent = s.sucursal_cod;
                sel.appendChild(opt);
            });
        });
}

function crearEnvio() {
    fetch(API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            fecha_envio:          document.getElementById('input-fecha').value,
            peso_kg:              document.getElementById('input-peso').value,
            tipo_cont_cod:        document.getElementById('input-tipo').value,
            remitente_dni:        document.getElementById('input-remitente').value,
            destinatario_dni:     document.getElementById('input-destinatario').value,
            sucursal_orig_cod:    document.getElementById('input-suc-orig').value,
            sucursal_dest_cod:    document.getElementById('input-suc-dest').value,
            direccion_entrega_id: document.getElementById('input-direccion').value
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.ok) {
            alert('Envío creado correctamente. Nro: ' + data.nro_envio_creado);
            listarEnvios(paginaActual);
        } else {
            alert('Error: ' + (data.error || 'No se pudo crear el envío'));
        }
    })
    .catch(err => {
        alert('Error de conexión: ' + err.message);
    });
}

function eliminarEnvio(nro) {
    if (!confirm('¿Seguro que querés eliminar este envío?\nEsta acción lo marcará como inactivo.')) return;
    fetch(API, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nro_envio: nro })
    })
    .then(res => res.json())
    .then(data => {
        alert(data.mensaje);
        listarEnvios(paginaActual);
    });
}

document.getElementById('btn-filtrar').addEventListener('click', () => listarEnvios(1));
document.getElementById('btn-limpiar').addEventListener('click', () => {
    document.getElementById('filtro-fecha-desde').value = '';
    document.getElementById('filtro-fecha-hasta').value = '';
    document.getElementById('filtro-sucursal-orig').value = '';
    document.getElementById('filtro-tipo').value = '';
    const chkInactivos = document.getElementById('filtro-mostrar-inactivos');
    if (chkInactivos) chkInactivos.checked = false;
    listarEnvios(1);
});

document.getElementById('th-nro-envio').addEventListener('click', () => ordenarPor('nro_envio'));
document.getElementById('th-fecha-envio').addEventListener('click', () => ordenarPor('fecha_envio'));
document.getElementById('th-peso-kg').addEventListener('click', () => ordenarPor('peso_kg'));

cargarSucursales();
listarEnvios();
