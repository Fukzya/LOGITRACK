// ==============================================================================================
// QUÉ ES:  llena los desplegables (select) con las listas de api/catalogos.php.
// PARA CAMBIAR: el contenido de las listas se cambia en api/catalogos.php.
// ==============================================================================================
// Llena un <select> con las opciones de una tabla catálogo (api/catalogos.php).
// Conserva las <option> que ya tenga el select (por ejemplo "Todos").
function cargarCatalogo(selectId, tipo) {
    return fetch(`../../api/catalogos.php?tipo=${tipo}`)
        .then(res => res.json())
        .then(opciones => llenarSelect(document.getElementById(selectId), opciones));
}

// Agrega las opciones [{cod, nombre, grupo?}] a un <select>. Si traen "grupo" (las sucursales traen
// su provincia), se agrupan con <optgroup> para encontrarlas más rápido.
function llenarSelect(select, opciones) {
    let grupoActual = null, destino = select;
    for (const o of opciones) {
        if (o.grupo !== undefined && o.grupo !== grupoActual) {
            grupoActual = o.grupo;
            destino = document.createElement('optgroup');
            destino.label = o.grupo;
            select.appendChild(destino);
        }
        destino.appendChild(new Option(o.nombre, o.cod));
    }
}
