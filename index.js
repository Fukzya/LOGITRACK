// ==============================================================================================
// QUÉ ES:  el comportamiento del menú principal: controla la sesión y llena las tarjetas "Hoy"
//          con los números de api/tablero.php. Los textos de las tarjetas están en index.php.
// ==============================================================================================
fetch('api/check_session.php')
  .then(res => res.json())
  .then(data => {
    if (!data.autenticado) {
      window.location.href = 'login.html' + (data.motivo && data.motivo !== 'SIN_SESION' ? '?motivo=' + data.motivo : '');
    }
  });

function cerrarSesion(e) {
  e.preventDefault();
  fetch('api/logout.php', { method: 'POST' })
    .finally(() => { window.location.href = 'login.html'; });
}

// ------------------------------------------------------------ tablero (sale de las vistas)
const escHtml = v => String(v ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const numero = n => Number(n).toLocaleString('es-AR');
const fechaIso = d => new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10);

fetch('api/tablero.php')
  .then(res => res.ok ? res.json() : Promise.reject())
  .then(t => {
    document.getElementById('tablero-pendientes').textContent = numero(t.pendientes.total);
    document.getElementById('tablero-pendientes-mes').textContent = `${numero(t.pendientes.este_mes)} recibidos este mes`;
    // Envíos sin viaje: en color de aviso si hay alguno; el link abre Envíos con el filtro "Sin viaje".
    const sinViaje = Number(t.sin_viaje.total);
    const linkSinViaje = document.getElementById('tablero-sin-viaje');
    document.getElementById('tablero-sin-viaje-texto').textContent =
      sinViaje === 1 ? '1 sin viaje asignado' : `${numero(sinViaje)} sin viaje asignado`;
    linkSinViaje.className += sinViaje > 0 ? ' bg-amber-50 text-amber-800 hover:bg-amber-100' : ' text-on-surface-variant hover:bg-surface-container';
    linkSinViaje.hidden = false;
    document.getElementById('tablero-en-curso').textContent = numero(t.en_curso.total);
    document.getElementById('tablero-atrasados').textContent = `${numero(t.en_curso.atrasados)} pasaron su llegada estimada`;
    document.getElementById('tablero-proximos').textContent = numero(t.proximos.total);
    const hoy = new Date(), en7 = new Date(Date.now() + 7 * 864e5);
    document.getElementById('tablero-link-proximos').href =
      `paginas/viajes/viajes.php?fecha_desde=${fechaIso(hoy)}&fecha_hasta=${fechaIso(en7)}`;
    document.getElementById('tablero-clientes').innerHTML = t.top_clientes.map(c =>
      `<li class="flex justify-between gap-2"><span class="truncate">${escHtml(c.nombre_completo)}</span><span class="text-on-surface-variant">${escHtml(c.total_envios)}</span></li>`
    ).join('');
  })
  .catch(() => { document.getElementById('tablero-pendientes').textContent = '-'; });
