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
  logitrack.sql   Volcado completo (mysqldump --routines --triggers), con los cambios 001-018
  cambios/        Scripts que modifican la base, numerados y en orden
docs/         teoria_campus.md: resumen del material de la cátedra por tema
index.php     Menú principal (requiere sesión)
login.html    Pantalla de login
```

## Instalación

1. Clonar el repo dentro de `C:\xampp\htdocs\` para que quede en `C:\xampp\htdocs\LOGITRACK`.
2. Levantar Apache y MySQL desde el panel de XAMPP.
3. En HeidiSQL, importar `database/logitrack.sql` (como root). Crea la base `logitrack` con todo:
   tablas, datos, vistas, procedimientos, funciones y triggers, con los cambios 001 a 018 ya
   aplicados. **No hay que correr esos scripts de nuevo.**
4. Correr, como root y en este orden, `database/cambios/016_cuentas_y_roles.sql` y
   `database/cambios/017_concurrencia_capacidad.sql`. Los usuarios, roles y permisos (GRANT) se
   guardan en la base del sistema `mysql`, no en `logitrack`, así que el volcado no los trae.
   016 crea las cuentas y sus permisos; 017 vuelve a crear sus triggers (iguales) y da el permiso
   sobre su función.
5. Darles contraseña a las cuentas de la app (016 las deja bloqueadas):
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

**Demo de concurrencia:** `bash database/pruebas/demo_concurrencia.sh` hace que dos sesiones
asignen envíos a la misma moto al mismo tiempo y muestra que no se supera la capacidad
(ver `docs/defensa_transacciones.md`). Borra lo que crea.

## Cambios a la base

Cada cambio de estructura va como un archivo nuevo en `database/cambios/`,
con número correlativo (`002_...sql`, `003_...sql`). No se edita un script
que ya se ejecutó: si hay que corregir algo, se agrega uno nuevo.

Después de cada script nuevo se regenera el volcado (también es el backup lógico de la base):

```
C:\xampp\mysql\bin\mysqldump -uroot -p --default-character-set=utf8mb4 --single-transaction
    --routines --triggers --databases logitrack --result-file=database\logitrack.sql
```

- `--routines --triggers`: sin estas opciones el volcado **no** trae procedimientos, funciones ni
  triggers (las vistas sí vienen siempre).
- `--single-transaction`: copia en caliente y consistente (una sola "foto" de InnoDB) sin bloquear
  a los que están usando la app.
- `--databases logitrack`: incluye el `CREATE DATABASE` y el `USE`.
- Los usuarios y permisos no viajan en el volcado (están en la base `mysql`): por eso el paso 4
  de la instalación.

## Pendientes conocidos

- [x] **Historial duplicado al crear un envío**: `procedimiento_crear_envio` y
      `trigger_after_insert_envio` insertaban los dos el estado "En sucursal origen".
      Corregido en `cambios/002` (queda solo el trigger).
- [x] Usuarios: el usuario `operador1` tenía rol `admin` en el volcado. Corregido en `cambios/002`.
