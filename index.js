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
