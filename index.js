fetch('api/check_session.php')
  .then(res => res.json())
  .then(data => {
    if (!data.autenticado) {
      window.location.href = 'login.html';
    }
  });

function cerrarSesion(e) {
  e.preventDefault();
  fetch('api/logout.php')
    .then(res => res.json())
    .then(() => { window.location.href = 'login.html'; });
}
