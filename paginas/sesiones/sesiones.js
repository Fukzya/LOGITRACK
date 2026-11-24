// Pantalla de sesiones (solo admin): sesiones de la aplicación abiertas, conexiones a la base
// (sesiones de MariaDB) e historial de sesiones cerradas.
const API_SESIONES = '../../api/sesiones.php';

const MOTIVOS = {
    LOGOUT: 'Cerró sesión',
    VENCIDA: 'Venció (30 min sin uso)',
    CERRADA_POR_ADMIN: 'La cerró un admin',
    BAJA_USUARIO: 'Usuario dado de baja',
    CAMBIO_ROL: 'Cambio de rol',
};

/** "2026-11-24 10:05:00" -> "24/11 10:05". */
function fechaCorta(valor) {
    if (!valor) return '';
    const [f, h] = String(valor).split(' ');
    const [, m, d] = f.split('-');
    return `${d}/${m} ${h.slice(0, 5)}`;
}

function minutos(n) {
    n = Number(n);
    if (n < 1) return 'menos de 1 min';
    if (n < 60) return `${n} min`;
    return `${Math.floor(n / 60)} h ${n % 60} min`;
}

// ------------------------------------------------------------ sesiones abiertas
let abiertas = new Map();

async function listarAbiertas() {
    try {
        const { datos } = await api(`${API_SESIONES}?ver=abiertas`);
        abiertas = new Map(datos.map(f => [String(f.sesion_id), f]));
        document.getElementById('tabla-abiertas').innerHTML = datos.map(f => `
            <tr>
                <td>${esc(f.username)} <small>${esc(f.rol)}${f.propia ? ' · esta sesión' : ''}</small></td>
                <td>${esc(fechaCorta(f.inicio))} <small>hace ${esc(minutos(f.minutos_conectado))}</small></td>
                <td>${esc(fechaCorta(f.ultimo_acceso))} <small>hace ${esc(minutos(f.minutos_sin_uso))}</small></td>
                <td>${esc(f.ip)}</td>
                <td title="${esc(f.navegador)}">${esc(navegador(f.navegador))}</td>
                <td>${f.propia ? '' : `<button data-accion="baja" data-clave="${esc(f.sesion_id)}">Cerrar</button>`}</td>
            </tr>`).join('') || '<tr><td colspan="6">No hay sesiones abiertas.</td></tr>';
    } catch (err) {
        mostrarError(err);
    }
}

/** Nombre corto del navegador a partir del User-Agent (el completo queda en el title). */
function navegador(ua) {
    ua = ua || '';
    const nombre = /Edg\//.test(ua) ? 'Edge' : /OPR\//.test(ua) ? 'Opera' : /Chrome\//.test(ua) ? 'Chrome'
                 : /Firefox\//.test(ua) ? 'Firefox' : /Safari\//.test(ua) ? 'Safari' : /curl\//.test(ua) ? 'curl' : 'Otro';
    const so = /Windows/.test(ua) ? 'Windows' : /Android/.test(ua) ? 'Android' : /iPhone|iPad/.test(ua) ? 'iOS'
             : /Mac OS/.test(ua) ? 'macOS' : /Linux/.test(ua) ? 'Linux' : '';
    return so ? `${nombre} en ${so}` : nombre;
}

document.getElementById('tabla-abiertas').addEventListener('click', async e => {
    const b = e.target.closest('button[data-accion]');
    if (!b) return;
    const f = abiertas.get(b.dataset.clave);
    if (!confirm(`¿Cerrar la sesión de ${f.username} (${navegador(f.navegador)})?`)) return;
    try {
        alert((await api(API_SESIONES, 'POST', { sesion_id: f.sesion_id })).mensaje);
        actualizar();
    } catch (err) {
        mostrarError(err);
    }
});

// ------------------------------------------------------------ conexiones a la base
async function listarConexiones() {
    try {
        const { datos, esta_conexion: c } = await api(`${API_SESIONES}?ver=conexiones`);
        document.getElementById('esta-conexion').innerHTML =
            `Este pedido usó la conexión <b>${esc(c.id)}</b>, con las variables de sesión ` +
            `<code>@usuario_app = ${esc(c.usuario_app)}</code> y <code>@sesion_app = ${esc(c.sesion_app)}</code>, ` +
            `y nivel de aislamiento <b>${esc(c.aislamiento)}</b>.`;
        document.getElementById('tabla-conexiones').innerHTML = datos.map(f => `
            <tr>
                <td>${esc(f.id)}${f.propia == 1 ? ' <small>este pedido</small>' : ''}</td>
                <td>${esc(f.usuario)}</td>
                <td>${esc(f.host)}</td>
                <td>${esc(f.base)}</td>
                <td>${esc(f.comando)}</td>
                <td>${esc(f.segundos)}</td>
                <td><small>${esc(f.consulta)}</small></td>
            </tr>`).join('');
    } catch (err) {
        mostrarError(err);
    }
}

// ------------------------------------------------------------ historial
const pantallaHistorial = pantallaCrud({
    api: API_SESIONES,
    clave: 'sesion_id',
    nombre: 'la sesión',
    tabla: 'tabla-historial',
    paginacion: 'paginacion',
    columnas: [
        f => esc(f.username),
        f => esc(fechaCorta(f.inicio)),
        f => esc(fechaCorta(f.fin)),
        f => esc(minutos(f.minutos)),
        f => esc(MOTIVOS[f.motivo_cierre] || f.motivo_cierre),
        f => esc(f.ip),
    ],
    filtros: { 'filtro-username': 'username' },
    orden: { col: 'fin', dir: 'DESC' },
    encabezados: { 'th-username': 'username', 'th-inicio': 'inicio', 'th-fin': 'fin', 'th-motivo': 'motivo' },
    editable: false,
    baja: null,
});

function actualizar() {
    listarAbiertas();
    listarConexiones();
    pantallaHistorial.listar();
}

document.getElementById('btn-actualizar').addEventListener('click', actualizar);
listarAbiertas();
listarConexiones();
