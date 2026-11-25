// Resumen por sucursal: llama a procedimiento_resumen_por_sucursal(desde, hasta) a través de
// api/sucursales.php?resumen=1. Por defecto muestra el mes actual.
const $ = id => document.getElementById(id);
const num = n => Number(n).toLocaleString('es-AR', { maximumFractionDigits: 2 });

async function cargar() {
    const params = new URLSearchParams({ resumen: 1 });
    if ($('filtro-desde').value) params.set('desde', $('filtro-desde').value);
    if ($('filtro-hasta').value) params.set('hasta', $('filtro-hasta').value);
    $('tabla-resumen').innerHTML = '<tr><td colspan="7">Calculando...</td></tr>';
    try {
        const filas = await api(`../../api/sucursales.php?${params}`);
        $('tabla-resumen').innerHTML = filas.map(f => `
            <tr>
                <td>${esc(f.sucursal_cod)} - ${esc(f.sucursal_completa)}</td>
                <td class="text-right">${num(f.despachados)}</td>
                <td class="text-right">${num(f.kg_despachados)}</td>
                <td class="text-right">${num(f.con_destino)}</td>
                <td class="text-right">${num(f.entregados)}</td>
                <td class="text-right">${num(f.viajes_salidos)}</td>
                <td class="text-right">${num(f.pendientes_hoy)}</td>
            </tr>`).join('') || '<tr><td colspan="7">No hay movimiento en ese período.</td></tr>';
        const total = filas.reduce((s, f) => s + Number(f.despachados), 0);
        $('total-resumen').textContent = `${filas.length} sucursal(es) con movimiento. Envíos despachados en el período: ${num(total)}.`;
    } catch (err) {
        $('tabla-resumen').innerHTML = '';
        mostrarError(err);
    }
}

$('form-periodo').addEventListener('submit', e => { e.preventDefault(); cargar(); });
$('btn-todo').addEventListener('click', () => { $('filtro-desde').value = ''; $('filtro-hasta').value = ''; cargar(); });

// Por defecto: desde el primer día del mes actual.
const hoy = new Date();
$('filtro-desde').value = `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, '0')}-01`;
cargar();
