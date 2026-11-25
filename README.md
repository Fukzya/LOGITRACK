# LogiTrack

Sistema de gestión de envíos y viajes, trabajo práctico de Base de Datos 2.
Es PHP con PDO y MariaDB, y corre sobre XAMPP.

## Estructura

El código sigue el patrón **MVC**:

```
modelos/      MODELO: una clase por entidad (Cliente.php, Envio.php...). Es el único lugar con SQL.
              Modelo.php es la clase base (listado paginado, filtros y orden).
api/          CONTROLADOR: un endpoint por entidad. Valida los datos, llama al modelo y responde JSON.
paginas/      VISTA: una carpeta por pantalla con x.php (estructura) y x.js (qué columnas, campos y
              filtros tiene). Comunes: plantilla.php (sesión, encabezado), crud.js (comportamiento de
              las pantallas), comun.js (api(), esc(), errores) y catalogos.js (desplegables).
config/       conexion.php (cuenta de base según el rol), session.php (login / sesiones),
              permisos.php (qué puede cada rol) y api.php (base de las APIs: validaciones,
              respuestas JSON y traducción de errores de la base)
database/
  logitrack.sql   Volcado completo de la base: tablas, datos, procedimientos, triggers y vistas
  cambios/        Scripts que modifican la base, numerados y en orden
docs/         teoria_campus.md: resumen del material de la cátedra por tema
index.php     Menú principal (requiere sesión)
login.html    Pantalla de login
```

## Instalación

1. Clonar el repo dentro de `C:\xampp\htdocs\` para que quede en `C:\xampp\htdocs\LOGITRACK`.
2. Levantar Apache y MySQL desde el panel de XAMPP.
3. En HeidiSQL, importar `database/logitrack.sql`. Crea la base `logitrack` con todo.
4. Ejecutar en orden los scripts de `database/cambios/` (como root).
5. Darles contraseña a las cuentas de la app (las crea el script 016, bloqueadas):
   `C:\xampp\php\php database\configurar_cuentas.php` (pide la contraseña de root).
   Genera `config/credenciales.php`, que no se versiona.
6. Abrir http://localhost/LOGITRACK/

**Seguridad:** la app nunca usa root. Se conecta con `logitrack_login`, `logitrack_operador` o
`logitrack_admin` según el rol de la sesión (mínimo privilegio, ver `docs/defensa_seguridad.md`).
El `.htaccess` impide descargar por la web `database/`, `docs/`, `config/`, `modelos/` y los
archivos `.md`/`.sql`.

**Memoria de MariaDB (importante con muchos datos):** XAMPP trae `innodb_buffer_pool_size=16M`.
En `C:\xampp\mysql\bin\my.ini` cambiarlo a `innodb_buffer_pool_size=2G` y reiniciar MySQL.
Con 100.000 envíos, la primera página de Envíos pasa de 31 s a 1,9 s (ver
`database/pruebas/`: `carga_100k.sql` genera los datos en una copia de la base y
`medir_consultas.sh` mide las consultas de la app).

## Cambios a la base

Cada cambio de estructura va como un archivo nuevo en `database/cambios/`,
con número correlativo (`002_...sql`, `003_...sql`). No se edita un script
que ya se ejecutó: si hay que corregir algo, se agrega uno nuevo.

Cada tanto conviene regenerar `database/logitrack.sql` desde HeidiSQL
(Exportar base de datos como SQL) para que el volcado quede al día.

## Pendientes conocidos

- [x] **Historial duplicado al crear un envío**: `procedimiento_crear_envio` y
      `trigger_after_insert_envio` insertaban los dos el estado "En sucursal origen".
      Corregido en `cambios/002` (queda solo el trigger).
- [x] Usuarios: el usuario `operador1` tenía rol `admin` en el volcado. Corregido en `cambios/002`.
