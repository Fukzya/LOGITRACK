// Llena un <select> con las opciones de una tabla catálogo (api/catalogos.php).
// Conserva las <option> que ya tenga el select (por ejemplo "Todos").
function cargarCatalogo(selectId, tipo) {
    return fetch(`../../api/catalogos.php?tipo=${tipo}`)
        .then(res => res.json())
        .then(opciones => {
            const select = document.getElementById(selectId);
            opciones.forEach(o => select.add(new Option(o.nombre, o.cod)));
        });
}
