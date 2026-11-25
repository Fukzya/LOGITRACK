// ==============================================================================================
// QUÉ ES:  el comportamiento de la pantalla de login (login.html).
// PARA CAMBIAR: los mensajes del login -> "CAMBIAR:" abajo.
// Guía con ejemplos: docs/como_cambiar.md (buscá "CAMBIAR:" con Ctrl+Shift+F).
// ==============================================================================================
// Rutas relativas: funcionan entre por localhost, 127.0.0.1 o el nombre de la PC.
const API_CHECK = 'api/check_session.php';
const API_LOGIN = 'api/login.php';

// Por qué se volvió al login (lo manda la pantalla o la API en ?motivo=...).
// CAMBIAR: el mensaje según por qué se volvió al login. Son los mismos que MENSAJES_SESION
//          en config/session.php: cambiar los dos.
const MOTIVOS = {
    VENCIDA:           'Tu sesión venció por inactividad. Volvé a entrar.',
    CERRADA_POR_ADMIN: 'Un administrador cerró tu sesión.',
    BAJA_USUARIO:      'Tu usuario fue dado de baja.',
    CAMBIO_ROL:        'Tu rol cambió. Volvé a entrar.',
};

document.addEventListener('DOMContentLoaded', function () {
    const motivo = new URLSearchParams(location.search).get('motivo');
    if (MOTIVOS[motivo]) {
        document.getElementById('mensaje').textContent = MOTIVOS[motivo];
    }

    fetch(API_CHECK)
        .then(res => res.json())
        .then(data => {
            if (data.autenticado) {
                window.location.href = 'index.php';
            }
        });

    document.getElementById('form-login').addEventListener('submit', function (e) {
        e.preventDefault();
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;

        fetch(API_LOGIN, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username: username, password: password })
        })
        .then(res => res.json())
        .then(data => {
            if (data.ok) {
                window.location.href = 'index.php';
            } else {
                document.getElementById('mensaje').textContent = data.mensaje;
            }
        });
    });
});
