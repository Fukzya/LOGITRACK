// Pantalla de usuarios del sistema (solo admin). Alta con el formulario; rol, contraseña y estado
// se cambian por separado desde cada fila.
const API_USUARIOS = '../../api/usuarios.php';
let usuarioPassword = null;   // usuario al que se le está cambiando la contraseña

async function cambiar(cuerpo, pregunta, pantalla) {
    if (pregunta && !confirm(pregunta)) return;
    try {
        alert((await api(API_USUARIOS, 'PUT', cuerpo)).mensaje);
        pantalla.listar();
    } catch (err) {
        mostrarError(err);
    }
}

const pantallaUsuarios = pantallaCrud({
    api: API_USUARIOS,
    clave: 'usuario_id',
    nombre: 'el usuario',
    tabla: 'tabla-usuarios',
    columnas: [f => esc(f.usuario_id), f => esc(f.username), f => esc(f.rol), celdaEstado],
    form: 'form-usuario',
    titulo: 'titulo-form',
    tituloAlta: 'Nuevo usuario',
    campos: { 'input-username': 'username', 'input-password': 'password', 'input-rol': 'rol' },
    orden: { col: 'usuario_id', dir: 'ASC' },
    encabezados: { 'th-id': 'usuario_id', 'th-username': 'username', 'th-rol': 'rol' },
    editable: false,
    baja: null,
    accionesExtra: f => `
        <button data-accion="rol" data-clave="${esc(f.usuario_id)}">Pasar a ${f.rol === 'admin' ? 'operador' : 'admin'}</button>
        <button data-accion="password" data-clave="${esc(f.usuario_id)}">Cambiar contraseña</button>
        <button data-accion="activo" data-clave="${esc(f.usuario_id)}">${f.activo == 1 ? 'Desactivar' : 'Activar'}</button>`,
    acciones: {
        rol: (f, p) => {
            const nuevo = f.rol === 'admin' ? 'operador' : 'admin';
            cambiar({ usuario_id: f.usuario_id, rol: nuevo }, `¿Cambiar el rol de ${f.username} a ${nuevo}?`, p);
        },
        activo: (f, p) => {
            const activar = f.activo != 1;
            cambiar({ usuario_id: f.usuario_id, activo: activar },
                    `¿${activar ? 'Activar' : 'Desactivar'} a ${f.username}?`, p);
        },
        password: f => {
            usuarioPassword = f;
            document.getElementById('titulo-password').textContent = `Nueva contraseña para ${f.username}`;
            document.getElementById('form-password').hidden = false;
            document.getElementById('input-password-nueva').focus();
        },
    },
});

function cerrarFormPassword() {
    usuarioPassword = null;
    document.getElementById('form-password').reset();
    document.getElementById('form-password').hidden = true;
}

document.getElementById('form-password').addEventListener('submit', async e => {
    e.preventDefault();
    const password = document.getElementById('input-password-nueva').value;
    await cambiar({ usuario_id: usuarioPassword.usuario_id, password }, null, pantallaUsuarios);
    cerrarFormPassword();
});
document.getElementById('btn-cancelar-password').addEventListener('click', cerrarFormPassword);
