# Cómo cambiar cosas en LogiTrack (sin saber PHP)

Arrancá por lo que querés hacer, no por el archivo. En el código, los lugares que se suelen tocar
están marcados con **`CAMBIAR:`**: en VS Code, `Ctrl+Shift+F` → `CAMBIAR:` lista todos. Además,
cada archivo empieza con un bloque **QUÉ ES / PARA CAMBIAR**.

## Mapa del proyecto

```
Navegador                         Servidor (PHP)                           Base (MariaDB)
paginas/<pantalla>/x.php  (diseño, HTML)
paginas/<pantalla>/x.js   (qué hace) ──pide──> api/x.php (revisa datos y permisos)
                                                  └──> modelos/X.php (el SQL) ──> tablas, triggers,
                                                                                  procedimientos, vistas
```

| Carpeta / archivo | Qué hay |
|---|---|
| `paginas/` | Las pantallas: una carpeta por pantalla, con su `.php` (diseño) y su `.js` (comportamiento) |
| `paginas/plantilla.php` | Lo común a todas las pantallas: menú lateral, colores, encabezado |
| `index.php` | El menú principal (tarjetas "Hoy"). **Repite el menú lateral** de la plantilla |
| `api/` | Lo que reciben las pantallas: valida los datos y los permisos |
| `modelos/` | Las consultas SQL (el único lugar que habla con la base) |
| `config/` | Sesión, permisos, conexión y validaciones comunes |
| `database/cambios/` | Historial de cambios de la base (uno por script, numerados) |
| `database/logitrack.sql` | El volcado completo de la base (se importa para instalar) |

## Reglas antes de tocar

1. **Cambios en la base** (tablas, triggers, procedimientos, vistas, permisos): nunca a mano en
   HeidiSQL ni en `logitrack.sql`. Se escribe un script nuevo `database/cambios/0NN_...sql` (el
   siguiente número), se corre y después **se regenera el volcado** (comando en el README).
2. **Después de cambiar un `.js` o un `.php`:** recargar la página con `Ctrl+F5`. Excepción:
   `login.html` es estático; si cambiás `login.js`, subí el número de `login.js?v=` en `login.html`.
3. **Probar** con los dos roles (admin y operador1) lo que cambiaste.

## Recetas

### Textos y aspecto

| Quiero... | Dónde | Cómo |
|---|---|---|
| Cambiar un título, una etiqueta o una ayuda de una pantalla | `paginas/<pantalla>/<pantalla>.php` | Buscar el texto (Ctrl+F) y cambiarlo en el HTML |
| Cambiar el texto de un botón de las filas ("Editar", "Dar de baja") | `paginas/crud.js` → `function botones` | Cambiar el texto entre `>` y `</button>` |
| Cambiar "No hay registros con esos filtros." | `paginas/crud.js` → `function listar` | |
| Cambiar un mensaje de error que dice "...es obligatorio" o "...debe tener el formato..." | `config/api.php` (funciones `campo_...`) | Es el mismo texto para todas las pantallas |
| Cambiar un mensaje de error que viene de una regla ("El vehículo del viaje no tiene capacidad...") | Está en la **base** (trigger o procedimiento) | Buscar el texto en `database/logitrack.sql` para saber en qué trigger está; volver a crearlo con un script nuevo |
| Cambiar los mensajes de sesión vencida o cerrada | `config/session.php` (`MENSAJES_SESION`) **y** `login.js` (`MOTIVOS`) | Los dos |
| Cambiar los colores | `paginas/plantilla.php`, `index.php` y `login.html` (`tailwind.config`, `colors`) | En los tres |
| Agregar, sacar o renombrar una opción del menú lateral | `paginas/plantilla.php` **y** `index.php` | Una línea `<a ...>` por opción, en los dos |

### Listados y formularios

| Quiero... | Dónde | Cómo |
|---|---|---|
| Que cada página muestre más o menos filas | `modelos/Modelo.php` → `POR_PAGINA` | Un número (hoy 50) |
| Sacar una columna de una tabla | `paginas/<pantalla>/<pantalla>.js` → `columnas` **y** su `<th>` en la `.php` | Borrar los dos, en la misma posición |
| Agregar una columna que ya trae la API | `columnas` del `.js` + un `<th>` en la `.php` | `f => esc(f.nombre_del_dato)` |
| Cambiar el orden inicial de un listado | `.js` de la pantalla → `orden` | `ASC` ascendente, `DESC` descendente |
| Permitir ordenar por otra columna | `modelos/X.php` → `ORDENABLES` + `encabezados` en el `.js` | |
| Cambiar el largo máximo o si un dato es obligatorio | `api/x.php` → `function datos_x` (envíos y viajes: `config/validaciones.php`) | El número del largo; `false` al final = opcional. Ojo: la columna de la base también tiene un largo máximo |
| Cambiar las opciones de un desplegable | `api/catalogos.php` | Cada lista es una consulta |
| Cambiar los clientes que muestra el buscador o desde cuántas letras busca | `paginas/selector_cliente.js` | `RESULTADOS_VISIBLES` y `t.length < 2` |

### Un dato nuevo (ejemplo: "observaciones" en Clientes)

Toca las 4 capas, en este orden:

1. **Base:** script nuevo `database/cambios/0NN_cliente_observaciones.sql`:
   ```sql
   ALTER TABLE cliente ADD COLUMN observaciones VARCHAR(200) NULL;
   GRANT UPDATE (observaciones) ON logitrack.cliente TO rol_operador;   -- el operador edita por columna
   ```
   Agregar ese `GRANT` también a la lista `$permisosExtra` de `database/configurar_cuentas.php` y
   correrlo (si no, se pierde al instalar).
2. **Modelo** `modelos/Cliente.php`: agregar `observaciones` en el INSERT y en el UPDATE.
3. **API** `api/clientes.php` → `datos_cliente`: `'observaciones' => campo_texto($d, 'observaciones', 'Las observaciones', 200, false),`
4. **Pantalla:** un `<input id="input-observaciones">` en `clientes.php` y `'input-observaciones': 'observaciones'` en `campos` de `clientes.js`.
5. Regenerar el volcado y anotar el cambio en la planilla.

### Permisos y reglas

| Quiero... | Dónde | Cómo |
|---|---|---|
| Que el operador pueda hacer algo más (por ejemplo, dar de baja clientes) | 1) `config/permisos.php`: agregar `'baja'` a `'clientes'`. 2) La base: `GRANT UPDATE (activo) ON logitrack.cliente TO rol_operador;` en un script nuevo **y** en `$permisosExtra` de `database/configurar_cuentas.php` | Si solo se hace el paso 1, la base lo rechaza: "Acceso denegado por la base" (es la segunda barrera, a propósito) |
| Cambiar cuánto dura la sesión sin uso | `config/session.php` → `MINUTOS_SESION` **y** la función `funcion_minutos_sesion` de la base (script nuevo con `CREATE OR REPLACE FUNCTION`) | Tienen que coincidir |
| Cambiar qué licencias manejan camiones | La función `funcion_licencia_maneja_especial` de la base (script nuevo con `CREATE OR REPLACE FUNCTION` + volver a dar su `GRANT EXECUTE` a `rol_operador`) | Un solo lugar: la usan el trigger del viaje y la pantalla "Nuevo envío" |
| Cambiar el mensaje de un CHECK de la base (ej. origen = destino) | `config/api.php` → `mensaje_check` | La regla está en la base; acá solo el texto |
| Cambiar el largo mínimo de las contraseñas | `api/usuarios.php` → `password_nueva` | |
| Cambiar cuánto espera la app si otro usuario tiene un viaje bloqueado | `config/conexion.php` → `innodb_lock_wait_timeout` | En segundos (hoy 10) |

### Menú principal y datos de prueba

| Quiero... | Dónde | Cómo |
|---|---|---|
| Cambiar "próximos 7 días" o el top 5 de clientes | `modelos/Tablero.php` (el número) + el texto en `index.php` | |
| Tener datos al día (viajes de esta semana y la que viene) | Consola: `C:\xampp\php\php database\pruebas\datos_de_hoy.php` | Borra los datos de 2025 en adelante y los regenera |
| Cambiar cuántos viajes o envíos genera, o sus pesos | `database/pruebas/datos_de_hoy.php` (`CAMBIAR:` arriba de todo y en `peso_para`) | |

## Si algo se rompe

| Síntoma | Causa probable |
|---|---|
| La pantalla queda en blanco o la tabla no carga | Error de sintaxis en el `.js`: `F12` → pestaña Consola muestra el archivo y la línea |
| "Ocurrió un error interno" | Error de PHP o de SQL: el detalle está en `C:\xampp\php\logs\php_error_log` (las últimas líneas; si no existe, en `C:\xampp\apache\logs\error.log`) |
| "Acceso denegado por la base de datos" | Falta un `GRANT` para la cuenta del rol (ver "Permisos y reglas") |
| "No se pudo conectar con la base de datos" | MySQL apagado en XAMPP, o falta `config/credenciales.php` (correr `database/configurar_cuentas.php`) |
| El cambio no se ve | Recargar con `Ctrl+F5` (el navegador guardó la versión vieja) |
