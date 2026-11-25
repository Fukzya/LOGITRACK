# LogiTrack: guía general de la defensa

Una línea por punto del checklist: qué hicimos, dónde está y qué mostrar. El detalle de cada tema
está en su guía (`docs/defensa_*.md`).

## El sistema en 30 segundos

Gestión de envíos de una empresa de logística: clientes mandan paquetes entre sucursales, los
paquetes viajan en vehículos con choferes (directo o con un trasbordo) y cada envío tiene un
historial de estados. PHP (PDO) + MariaDB 10.4 sobre XAMPP, pantallas con JavaScript.

**Arquitectura en capas (MVC):** `paginas/` (vista) → `api/` (controlador: valida y responde
JSON) → `modelos/` (único lugar con SQL) → MariaDB (reglas con triggers, procedimientos, vistas y
permisos). Datos: 8.089 envíos, 31.300 estados, 3.231 viajes, 5.001 clientes.

## Checklist

| Tema | Qué hicimos | Dónde | Demo (1 minuto) | Guía |
|---|---|---|---|---|
| **Normalización** | De 0FN a 3FN: 20 tablas (17 del negocio + usuario, sesion y auditoria). Catálogos para no repetir textos (estados, tipos de incidente, licencias). La provincia de una sucursal no se guarda (dependencia transitiva): la arma una vista | Planilla `logitrack_completo` (pestañas 0FN a 3FN y "Cambios en la base") | Mostrar la pestaña 3FN y `vista_sucursal_ubicacion` | Planilla |
| **CRUD** | 15 entidades con alta, listado (filtros, orden, páginas), edición y baja lógica (`activo`) o física. Asistente "Nuevo envío" de 4 pasos | `api/`, `modelos/`, `paginas/` + `crud.js` | Crear un cliente desde el asistente y enviarle un paquete | — |
| **Sesiones** | Sesión de la app (cookie HttpOnly + SameSite) registrada en la tabla `sesion`; la base decide el vencimiento (30 min). Un trigger cierra las sesiones de un usuario dado de baja. Pantalla para ver y cerrar sesiones y conexiones | `config/session.php`, script 011, `paginas/sesiones` | Admin cierra la sesión de un operador → el operador vuelve al login con el motivo | `defensa_sesiones.md` |
| **Procedimientos y funciones** | 13 procedimientos y 6 funciones. Modelo: `procedimiento_cerrar_viaje` (transacción + FOR UPDATE + EXIT HANDLER + parámetro OUT). Funciones usadas en vistas, triggers y PHP | Scripts 007, 011, 012, 017 | Cerrar el viaje 3232 → "2 envíos pasaron a En sucursal". El caso con ROLLBACK está en la guía | `defensa_procedimientos.md` |
| **Triggers** | 17 triggers. BEFORE valida (fechas, capacidad, licencia C/E, trasbordos) y AFTER registra (estado inicial, auditoría). Historial de estados inmutable | Scripts 007, 009, 013, 017 | `UPDATE historial_estado ...` → rechazado. Crear un viaje con camión y chofer con licencia B → mensaje de la base | `defensa_triggers.md` |
| **Vistas** | 8 vistas en uso: listados, tablero del menú, viajes programados y en curso, pendientes | Script 014, `modelos/Tablero.php` | Menú → tarjetas "Hoy". `SELECT * FROM vista_envios_pendientes` | `defensa_vistas.md` |
| **Seguridad en la base** | La app nunca usa root: una cuenta por rol (`rol_operador`, `rol_admin`, `rol_login`), GRANT por tabla, **por columna** y por procedimiento; sin DDL. root con contraseña | Script 016, `config/conexion.php` | Como `logitrack_operador`: `DELETE FROM sucursal` → *command denied* | `defensa_seguridad.md` |
| **Seguridad en el código** | Permisos por rol en todas las APIs (`config/permisos.php`), consultas preparadas, `esc()` contra XSS, contraseñas con `password_hash`, errores sin datos internos, `.htaccess` | `config/`, `.htaccess` | Operador → Sucursales en "Solo consulta". `/database/logitrack.sql` → 403 | `defensa_seguridad.md` |
| **Transacciones** | 6 explícitas: 5 en PHP (asistente...) y 1 en la base (cerrar viaje). Arreglo de concurrencia: la capacidad de un viaje no se supera aunque dos asignen a la vez (FOR UPDATE + lectura con bloqueo) | Script 017, `api/asistente_envio.php` | `bash database/pruebas/demo_concurrencia.sh` | `defensa_transacciones.md` |
| **Índices / EXPLAIN** | 3 índices para volumen (015) y 2 redundantes borrados (018). Prueba con 100.000 envíos | Scripts 015 y 018, `database/pruebas/` | `EXPLAIN` del filtro por fecha (`range`, 83 filas) contra `YEAR()` (recorre todo) | `defensa_indices.md` |
| **Backup** | Volcado lógico con `mysqldump --routines --triggers`; la instalación es importarlo + crear las cuentas | `database/logitrack.sql`, README | Explicar por qué sin esas opciones se pierden procedimientos y triggers | README |

## Ideas que conectan todo (para cualquier pregunta)

1. **Las reglas van en la base.** Triggers, procedimientos y permisos valen para cualquier programa
   (la app, HeidiSQL, un script), no solo para las pantallas.
2. **Dos barreras.** PHP controla el rol y la base vuelve a controlar con GRANT. Si PHP falla, la
   base frena.
3. **Todo o nada.** Lo que tiene que salir junto va en una transacción: en PHP (asistente) o en la
   base (cerrar viaje).
4. **Medir antes de cambiar.** Cada decisión de rendimiento tiene un EXPLAIN o un tiempo medido.
5. **Los datos de 2024 se generaron al azar** y no cumplen todas las reglas nuevas (estados antes
   de la fecha del envío, envíos con muchos viajes). No se corrigen: los triggers controlan todo lo
   que se carga desde ahora.

## Antes de la defensa

| Cuándo | Qué |
|---|---|
| Unos días antes | Cerrar los viajes de prueba que ya salieron (con los días van quedando atrasados y el tablero los muestra). Dejar **3231, 3232 y 3233** abiertos: son los de la demo de cerrar viaje |
| El día antes | Ensayar las demos. Si al ensayar se cierran los viajes de la demo, se recupera todo importando de nuevo `database/logitrack.sql` y corriendo `php database/configurar_cuentas.php` (se pierde lo cargado después del volcado) |
| En la defensa | Tener abiertos: la app (admin y operador en dos navegadores), HeidiSQL con dos pestañas de consulta y la planilla |

## Orden sugerido (10 minutos)

1. El sistema y la normalización (planilla): 1 min.
2. Recorrido de la app como operador: asistente "Nuevo envío" con trasbordo: 2 min.
3. Reglas en la base: un trigger que rechaza y un procedimiento (cerrar viaje): 2 min.
4. Seguridad: operador en "Solo consulta" + el DELETE rechazado por la base: 2 min.
5. Transacciones: la demo de concurrencia: 2 min.
6. Índices: un EXPLAIN antes y después: 1 min.
