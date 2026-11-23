const API_CHECK = 'http://localhost/logitrack/api/check_session.php';
const API_LOGIN = 'http://localhost/logitrack/api/login.php';

document.addEventListener('DOMContentLoaded', function () {
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
