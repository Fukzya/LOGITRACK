// Utilidades compartidas por todas las pantallas (capa Vista).

// Si la sesión venció, volver al login.
fetch('../../api/check_session.php')
    .then(res => res.json())
    .then(data => { if (!data.autenticado) window.location.href = '../../login.html'; });

function cerrarSesion(e) {
    e.preventDefault();
    fetch('../../api/logout.php').finally(() => { window.location.href = '../../login.html'; });
}

/**
 * Escapa un valor antes de meterlo en HTML. Todo dato que viene de la base pasa por acá:
 * así un nombre como O'Higgins no rompe la tabla y un texto con <script> no se ejecuta.
 */
function esc(valor) {
    return String(valor ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/**
 * Llama a la API y devuelve el JSON. Si la API responde con error, lanza un Error con el
 * mensaje que mandó el servidor ({"error": "..."}), para mostrarlo con mostrarError().
 *   api('../../api/clientes.php')                       -> GET
 *   api('../../api/clientes.php', 'POST', {nombre: ...}) -> POST con cuerpo JSON
 */
async function api(url, metodo = 'GET', cuerpo = null) {
    const opciones = { method: metodo, headers: {} };
    if (cuerpo !== null) {
        opciones.headers['Content-Type'] = 'application/json';
        opciones.body = JSON.stringify(cuerpo);
    }
    const res = await fetch(url, opciones);
    let data;
    try {
        data = await res.json();
    } catch {
        throw new Error('El servidor devolvió una respuesta inválida.');
    }
    if (res.status === 401) {
        window.location.href = '../../login.html';
        throw new Error('La sesión expiró.');
    }
    if (!res.ok) throw new Error(data.error || 'No se pudo completar la operación.');
    return data;
}

function mostrarError(err) {
    alert('Error: ' + err.message);
}
