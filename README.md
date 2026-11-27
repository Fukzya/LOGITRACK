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
  logitrack.sql   Volcado completo (mysqldump --routines --triggers), con los cambios 001-021 y datos actuales
  cambios/        Historial de cambios a la base (ya aplicados en el volcado: no se ejecutan)
docs/         como_cambiar.md (recetas para cambiar cosas), defensa_general.md (la defensa) y una guía por tema
index.php     Menú principal (requiere sesión)
login.html    Pantalla de login
```

## Instalación

1. Clonar el repo dentro de `C:\xampp\htdocs\` para que quede en `C:\xampp\htdocs\LOGITRACK`.
2. Levantar Apache y MySQL desde el panel de XAMPP.
3. En HeidiSQL, importar `database/logitrack.sql` (como root). Crea la base `logitrack` completa:
   tablas, datos, vistas, procedimientos, funciones y triggers.
4. Crear las cuentas de la aplicación, en una consola dentro de la carpeta del proyecto:
   `C:\xampp\php\php database\configurar_cuentas.php` (pide la contraseña de root; Enter si no
   tiene). Crea los roles, los permisos y las cuentas con contraseñas al azar, y las guarda en
   `config/credenciales.php`, que no se versiona. Hace falta porque los usuarios y permisos se
   guardan en la base del sistema `mysql`, no en `logitrack`, y el volcado no los trae.
5. Abrir http://localhost/LOGITRACK/

No hay que ejecutar ningún script de `database/cambios/`: ya están aplicados en el volcado.

**Seguridad:** la app nunca usa root. Se conecta con `logitrack_login`, `logitrack_operador` o
`logitrack_admin` según el rol de la sesión (mínimo privilegio, ver `docs/defensa_seguridad.md`).
El `.htaccess` impide descargar por la web `database/`, `docs/`, `config/`, `modelos/` y los
archivos `.md`/`.sql`.

**Memoria de MariaDB (importante con muchos datos):** XAMPP trae `innodb_buffer_pool_size=16M`.
En `C:\xampp\mysql\bin\my.ini` cambiarlo a `innodb_buffer_pool_size=2G` y reiniciar MySQL.
Con 100.000 envíos, la primera página de Envíos pasa de 31 s a 1,9 s (ver
`database/pruebas/`: `carga_100k.sql` genera los datos en una copia de la base y
`medir_consultas.sh` mide las consultas de la app).

**Datos actuales para usar la app:** `C:\xampp\php\php database\pruebas\datos_de_hoy.php` borra
los viajes y envíos de 2025 en adelante y genera unos nuevos, desde 3 días antes de hoy hasta el
domingo de la semana que viene (viajes cerrados, en curso y programados, envíos en todos los
estados, trasbordos e incidentes). Conviene correrlo antes de usar o mostrar la app. Los datos de
2024 quedan como historia.

**Demo de concurrencia:** `bash database/pruebas/demo_concurrencia.sh` hace que dos sesiones
asignen envíos a la misma moto al mismo tiempo y muestra que no se supera la capacidad
(ver `docs/defensa_transacciones.md`). Borra lo que crea.

## Cambios a la base

Para el desarrollo: cada cambio de estructura se escribió como un archivo nuevo en
`database/cambios/`, con número correlativo y un encabezado que explica qué cambió y por qué.
Quedan como historial (y para la defensa); para instalar no se usan.

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
  de la instalación (`configurar_cuentas.php`).

## Pendientes conocidos

- [x] **Historial duplicado al crear un envío**: `procedimiento_crear_envio` y
      `trigger_after_insert_envio` insertaban los dos el estado "En sucursal origen".
      Corregido en `cambios/002` (queda solo el trigger).
- [x] Usuarios: el usuario `operador1` tenía rol `admin` en el volcado. Corregido en `cambios/002`.
