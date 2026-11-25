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
        // La respuesta se lee como texto: si PHP falla devuelve HTML (no JSON) y hay que avisarlo igual.
        .then(res => res.text().then(texto => {
            let data;
            try { data = JSON.parse(texto); } catch (err) { data = null; }
            if (data && data.ok) {
                window.location.href = 'index.php';
                return;
            }
            // "mensaje" = usuario o contraseña; "error" = falla de la base (por ejemplo, faltan las
            // cuentas: correr database/configurar_cuentas.php).
            // CAMBIAR: el texto si el servidor responde algo inesperado.
            document.getElementById('mensaje').textContent =
                (data && (data.mensaje || data.error)) ||
                'Error del servidor (' + res.status + '). Detalle en C:\\xampp\\php\\logs\\php_error_log.';
        }))
        .catch(() => {
            document.getElementById('mensaje').textContent = 'No se pudo contactar al servidor (¿Apache está prendido?).';
        });
    });
});
