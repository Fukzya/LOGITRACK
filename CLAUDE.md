# LogiTrack - contexto para Claude

TP de la materia Bases de Datos II (UCH 2026). PHP (PDO) + MariaDB 10.4 sobre XAMPP, front con JS
(fetch) y Tailwind. Ver README.md para la estructura y la instalación.
Objetivo: cumplir el CHECKLIST de la cátedra (normalización, sesiones, procedimientos/funciones,
triggers, vistas, seguridad en BD y código, CRUD, transacciones, índices) y poder defenderlo.

## Reglas de trabajo (pedidos explícitos del usuario)
- Responder en español. Nada de textos largos: corto, didáctico, con tablas cuando ayuden.
- Commits y PRs SIN línea `Co-Authored-By` ni ninguna atribución a Claude.
- Repo: https://github.com/Fukzya/LOGITRACK, rama main. Hoy trabaja SOLO en la PC: no hacer
  `git pull` al empezar. Al terminar cada tarea: commit + `git push`.
  En PowerShell 5.1 los mensajes con comillas se rompen: escribir el mensaje en un archivo del
  scratchpad y usar `git commit -F archivo`.
- Cambios a la base: NUNCA editar `database/logitrack.sql` a mano. Cada cambio es un script nuevo
  numerado en `database/cambios/` (el próximo es 018_...), con un encabezado que explica qué y por
  qué, y se corre con `Get-Content -Raw -Encoding UTF8 script.sql | mysql
  --defaults-extra-file=C:/Users/santi/.logitrack/root.cnf --default-character-set=utf8mb4 logitrack`
  (mysql en c:\xampp\mysql\bin). Desde 016 root TIENE contraseña: está en ese archivo, fuera del
  repo (nunca mostrarla ni versionarla). Tabla, vista o rutina nueva que use la app = agregar sus
  GRANT a rol_operador / rol_admin (si no, la app da 403 "Acceso denegado por la base").
- Cada cambio de la base se documenta TAMBIÉN en la planilla (ver "Planilla").
- Antes de proponer, analizar los datos reales de la base (consultas) y decir lo que no cierra.
- Probar todo antes de entregar: API con curl (bash, cookie jar en el scratchpad) y pantallas con
  Playwright. Para la sesión se crea un usuario temporal `zz_prueba_op` (y `zz_prueba_admin` si hace falta admin; borrar también sus filas de `sesion`) (password_hash con php -r)
  y se borra al terminar, junto con los datos de prueba (los rechazos de transacciones dejan
  huecos en los AUTO_INCREMENT: es normal). Borrar la carpeta `.playwright-mcp/` antes del commit.
- Si el usuario no está de acuerdo con algo o no lo entiende, explicarle con ejemplos concretos y
  recomendar una opción; no implementar decisiones de diseño grandes sin su OK.

## Forma de trabajo por tema del checklist
Orden acordado: CRUD → sesiones → procedimientos → triggers → vistas → seguridad → transacciones
(+ índices/EXPLAIN). Para cada tema:
1. Análisis: qué está hecho y qué falta según la teoría (`docs/teoria_campus.md`, NO releer los PDF
   de `base de datos 2 campus/`) y según el código/base reales.
2. Explicación del concepto con ejemplos de LogiTrack (antes de pedir decisiones).
3. Plan COMPLETO (no resumido). Esperar la aprobación antes de implementar.
4. Implementar, probar, commit/push, actualizar planilla y este archivo.
5. Resumen corto y didáctico para la defensa: qué es, de qué se encarga, dónde y cómo lo aplicamos.

## Estado del checklist (2026-11-25)
- Normalización: hecha en la planilla (0FN a 3FN), 17 tablas + USUARIO. Actualizada con 007.
- CRUD: terminado (MVC, 15 entidades).
- Sesiones: TERMINADO (011). config/session.php es el único control (API verificar_sesion,
  pantallas verificar_sesion_pagina(raíz), check_session); cookie HttpOnly + SameSite=Strict +
  use_strict_mode; tabla sesion en la base decide el vencimiento (funcion_sesion_activa, 30 min),
  trigger en usuario cierra sesiones por baja/cambio de rol; pantalla paginas/sesiones (admin);
  login.html?motivo=...; logout solo POST. Varias sesiones por usuario permitidas (decisión).
  Guía de defensa: docs/defensa_sesiones.md. Demo de FOR UPDATE / aislamiento queda para transacciones.
- Procedimientos y funciones: TERMINADO (012). Todos los procedimientos tienen uso.
  procedimiento_cerrar_viaje = ejemplo modelo (START TRANSACTION + FOR UPDATE + EXIT HANDLER con
  ROLLBACK/RESIGNAL + OUT); registrar_estado_envio (OUT id) en "Cambiar estado" (Envíos) e
  Historial; resumen_por_sucursal(desde, hasta) en la pantalla paginas/resumen_sucursales.
  Funciones funcion_carga_viaje, funcion_ultimo_estado, funcion_envio_terminado (usadas en
  triggers, vista, procedimientos y modelos). Pantalla paginas/viajes/detalle.php (carga,
  envíos, "Agregar envíos pendientes" en una transacción, "Cerrar viaje"). api/cerrar_viaje.php
  borrado: todo va por api/viajes.php (GET ?detalle, POST accion=cerrar|agregar_envios).
  Lecciones medidas: función en vista cruzada consigo misma -> ALGORITHM=TEMPTABLE (1,6 s ->
  0,13 s); no hacer JOIN ... ON col = funcion() (se evalúa por fila del catálogo): el nombre del
  estado se completa en PHP (Modelo::conNombreDeEstado). Guía: docs/defensa_procedimientos.md.
  Demo: viajes 3231-3233 (salieron 24/11, en curso); para el ROLLBACK, estado Demorado 24/11
  20:00 al 8118 y cerrar 3233 con llegada 24/11 18:00.
- Triggers: TERMINADO (013). 17 triggers. Historial inmutable en la base (UPDATE/DELETE
  rechazados; para limpiar datos de prueba: `SET @mantenimiento = 1` en esa conexión). Tabla
  auditoria + procedimiento_auditar, triggers AFTER en envio, viaje (UPDATE y DELETE) y usuario
  (usuario = @usuario_app o CURRENT_USER()); pantalla paginas/auditoria (admin) y botón "Ver
  cambios" en Envíos y Viajes. Reglas del asistente en BEFORE (licencia C/E, vehículo y chofer
  activos y libres ese día, dirección del destinatario), solo en alta o si cambian esas columnas.
  REDIRIGIDO en la sucursal del último estado. Guía: docs/defensa_triggers.md.
- Vistas: TERMINADO (014). Las 8 vistas en uso. Envio y HistorialEstado listan desde
  vista_envios_detalle / vista_historial_completo. Menú principal: sección "Hoy" con 4 tarjetas
  (api/tablero.php, modelos/Tablero.php) desde vista_envios_pendientes, vista_viajes_en_curso
  (reemplazó a vista_viajes_activos), vista_viajes_programados y vista_clientes_con_envios.
  Filtros "Solo pendientes" (Envíos, ~1,5 s: funcion_envio_terminado sobre 8.000 envíos, revisar
  en índices) y "Solo en curso" (Viajes, usa la vista). crud.js ya carga checkbox desde la URL.
  Medido: función dentro de una vista + ORDER BY -> filesort calcula la función para todas las
  filas (550 ms -> 110 ms sacándola; Modelo::conNombreDeEstado la calcula para la página).
  Guía: docs/defensa_vistas.md. La vista con columnas ocultas + GRANT queda para seguridad.
- Seguridad PARTE 1: TERMINADA (016). El usuario eligió los 3 problemas más graves + contraseña de root.
  (1) Cuentas de base por rol (decisión del usuario: una por rol, no una sola): rol_login /
  rol_operador / rol_admin (hereda operador), cuentas logitrack_login/_operador/_admin@localhost;
  config/conexion.php elige la cuenta por $_SESSION['rol'] (conexion('login') en api/login.php e
  iniciar_sesion). Claves al azar en config/credenciales.php (.gitignore), las genera
  database/configurar_cuentas.php (CLI). GRANT por columna (operador sin `activo`), envío/estado/
  asignación solo por procedimiento (api/viaje_envio.php pasó a asignarConProcedimiento), nadie
  UPDATE/DELETE en historial ni auditoria, PROCESS solo admin. Errores 1142/1143/1370 -> 403.
  (2) config/permisos.php = tabla de permisos del operador (lista blanca; admin todo):
  exigir_permiso_crud('entidad') en cada API, exigir_permiso() en asistente/viaje_completo/
  usuarios/sesiones/auditoria; plantilla pasa PERMISOS al JS, crud.js usa cfg.entidad (oculta
  form y botones, muestra "Solo consulta"). Operador: alta/edición de envíos, clientes,
  direcciones, viajes, incidentes; asignar/quitar; cerrar viaje; NO bajas; recursos y catálogos
  solo ver. (3) .htaccess raíz: -Indexes, 403 a database/ docs/ config/ modelos/ .git/ campus y
  a *.md *.sql *.sh *.ini. Además: CORS * sacado; root/pma con clave
  (C:\Users\santi\.logitrack\root.cnf), phpMyAdmin auth cookie (copia config.inc.php.antes_seguridad).
  Guía: docs/defensa_seguridad.md.
- Seguridad PARTE 2 (pendiente, el usuario la dejó para después): límite de intentos del login,
  auditor externo con vista + masking (GRANT SELECT solo a la vista), backups con
  mysqldump --routines --triggers, calidad de datos (901 pares nombre+apellido repetidos,
  direcciones de terceros), explicar SSL (no aplica: misma PC).
- Transacciones: TERMINADO (017). 6 explícitas: 5 en PHP (asistente, agregar envíos, viaje
  completo, cliente+dirección, alta de envío) y 1 en la base (cerrar_viaje con EXIT HANDLER).
  Decidido: el asistente SIGUE en PHP (mostrar las dos capas). Hallazgo probado: moto de 50 kg con
  60 kg por dos asignaciones simultáneas, incluso por el asistente con FOR UPDATE (REPEATABLE READ:
  la foto se saca en la 1.ª lectura y el asistente leía antes de bloquear). Arreglo en la base:
  trigger_before_insert_viaje_envio con FOR UPDATE del viaje + funcion_carga_viaje_bloqueando
  (LOCK IN SHARE MODE), también en trigger_before_update_viaje; funcion_carga_viaje sin cambios
  (vistas/listados). PHP: asistente bloquea antes de crear el envío; bloquearParaAsignar usa la
  función con bloqueo; innodb_lock_wait_timeout = 10 en la conexión; 1205/1213 -> 409.
  Reglas: bloquear primero y leer después, o leer con bloqueo. Un procedimiento con START
  TRANSACTION no se llama dentro de otra transacción. Demo: database/pruebas/demo_concurrencia.sh
  (borra solo lo suyo). Al borrar datos de prueba con trasbordo: primero tramo 2. Guía:
  docs/defensa_transacciones.md.
- Índices / volumen (015, versión mínima acordada): el usuario NO quiere reworks grandes para un
  caso que no conocemos (el script de 100.000 registros lo traen los profesores). Hecho: my.ini
  innodb_buffer_pool_size 16M -> 2G (copia en my.ini.antes_rendimiento), 3 índices (015),
  database/pruebas/carga_100k.sql (copia logitrack_carga) y medir_consultas.sh. Con 100k:
  tablero 1,8 s, Resumen 14 s, Solo pendientes 6,6 s; el resto < 0,3 s.
  Arreglo chico aprobado y hecho: Modelo::paginarPorClave (cuenta y elige las 50 claves en la
  tabla, después trae esas filas de la vista) en Envio e HistorialEstado: con 100k, Envíos
  1,85 s -> 95 ms, Historial 0,9 s -> 104 ms (resultados comparados contra la vista: iguales).
  Arreglo grande NO aprobado (no hacer): estado actual precalculado por trigger para pendientes,
  tablero y resumen (funciones fila por fila: 6,8 s con 100k). Riesgo: los triggers rechazan
  datos inválidos del script ajeno. La copia logitrack_carga YA NO está en la PC (25/11): para
  volver a medir, regenerarla con carga_100k.sql (medir_consultas.sh usa root.cnf).
  Guía: docs/defensa_indices.md (EXPLAIN/ANALYZE reales con y sin cada índice vía IGNORE INDEX).
  Hallazgo (preguntado al usuario, sin decidir): 2 índices redundantes por prefijo izquierdo,
  idx_historial_envio (cubierto por idx_historial_envio_fecha) y fk_ve_envio (cubierto por el
  UNIQUE nro_envio+tramo). Borrarlos sería el 018.
  Lo que falta del checklist (orden acordado): guía de índices (hecha) -> regenerar
  database/logitrack.sql con --routines --triggers -> instalación desde cero -> guía general.

## Pendiente inmediato
- Próximo: seguridad parte 2 (preguntar). Ojo: con el paso de los días los
  viajes de prueba de 2026 van quedando "salidos sin cerrar" (es normal, se cierran con el botón).
- Usuarios de prueba: insertarlos como root (la cuenta de la app no puede); al borrar, también sus
  filas de sesion y de auditoria.
- Decidido NO hacer: "envío programado" (fecha de envío futura). fecha_envio = día en que se
  recibe el paquete; lo que es futuro es el viaje al que se asigna.

## Cambios en la base (database/cambios/, todos aplicados en la PC)
- 001 activo (baja lógica). 002 historial duplicado + operador1 a rol operador.
- 003 tablas catálogo estado_envio, tipo_incidente, tipo_licencia (estados en códigos RECIBIDO,
  EN_TRANSITO, EN_SUCURSAL, DEMORADO, REDIRIGIDO, ENTREGADO, DEVUELTO; es_final en ENTREGADO/DEVUELTO).
- 004 mails con tildes. 005 30 viajes de prueba futuros.
- 006 vista `vista_sucursal_ubicacion`: toda sucursal se muestra con su provincia
  ("Sucursal Puerto 46 (Mendoza)", campo sucursal_completa). Pedido del usuario.
- 007 rutas y trasbordos: viaje.sucursal_origen_cod / sucursal_destino_cod (FK, CHECK distintas),
  viaje.llegada_estimada = columna VIRTUAL fecha_salida + 24 h (decisión del usuario),
  viaje_envio.tramo (UNIQUE nro_envio+tramo, máx. 1 trasbordo = 2 tramos),
  trigger_before_insert_viaje_envio / trigger_before_delete_viaje_envio,
  vista_viajes_programados, procedimiento_buscar_rutas(origen, destino, peso).
- 008 ~196 viajes de prueba por centros 010 (Córdoba) y 012 (Buenos Aires).
- 009 reglas de fechas con triggers: estado no futuro / no antes de la fecha del envío / no antes
  del último / nada después de uno final; fecha_envio no futura; llegada real no futura;
  incidente dentro del viaje (procedimiento_validar_fecha_incidente); no asignar a viajes que ya
  salieron; editar un viaje no puede romper un trasbordo ni superar la capacidad.
- 010 limpieza: envíos 8019/8104/8110 a fecha 23/11, borrados los estados 31332 y 31244, viaje 3001 sin llegada real. Los envíos 8015-8018 (junio) tienen RECIBIDO 1 día antes de fecha_envio: viejos, no se tocan.
- 011 sesiones: tabla sesion (token_hash SHA-256, motivo_cierre ENUM, CHECK fin/motivo),
  funcion_minutos_sesion, funcion_sesion_activa, procedimiento_iniciar_sesion / cerrar_sesion /
  cerrar_sesiones_vencidas, trigger_after_update_usuario, vista_sesiones_abiertas.
- 012 procedimientos y funciones: funcion_carga_viaje / funcion_ultimo_estado /
  funcion_envio_terminado; cerrar_viaje, registrar_estado_envio y resumen_por_sucursal
  recreados; vista_viajes_programados TEMPTABLE con la función; triggers de viaje y viaje_envio
  usan las funciones; mensajes del trigger de historial con el n.º de envío; demo 3231-3233.
- 013 triggers: historial inmutable (+ @mantenimiento), tabla auditoria y triggers AFTER de
  auditoría (envio, viaje, usuario), reglas de recursos de viaje y dirección del destinatario en
  BEFORE (procedimiento_validar_recursos_viaje / _direccion_envio), REDIRIGIDO corregido.
  Planilla: AUDITORIA en "Direcciones y Usuarios" (U:AC) y en 3FN (tablas del sistema).
- 014 vistas: envios_detalle (sin la función), historial_completo (sin clientes),
  envios_pendientes (último por fecha, solo activos), viajes_en_curso (borrada viajes_activos),
  clientes_con_envios con subconsultas (0,5 s -> 0,1 s).
- 015 índices: historial (nro_envio, fecha_hora, id_historial), envio (fecha_envio), viaje (fecha_salida).
- 016 seguridad: roles rol_login/rol_operador/rol_admin, cuentas logitrack_* (ACCOUNT LOCK hasta
  configurar_cuentas.php), GRANT por tabla/columna/rutina, PROCESS a admin. Re-ejecutable (DROP primero).
- 017 concurrencia: funcion_carga_viaje_bloqueando; triggers before_insert_viaje_envio (FOR UPDATE
  del viaje) y before_update_viaje recreados; GRANT EXECUTE a rol_operador.
- Los datos de prueba originales (2024) se generaron al azar y NO cumplen las reglas nuevas
  (envíos con hasta 9 viajes, estados antes de la fecha del envío, etc.). No se corrigen: los
  triggers controlan lo nuevo. Decirlo así en la defensa.
- Sugerido al usuario y pendiente: regenerar database/logitrack.sql desde HeidiSQL con
  `--routines --triggers` para que el volcado incluya 002-015.

## Planilla (Google Sheets, conector de Sheets)
- logitrack_completo, id 1EoWc3-FiO3lrLnfPtyVn2zzXPr9a5W_p6m-kMcvyQV8 (locale en_US: fórmulas con
  comas). Pestañas: Normalización (4000), 0FN-3FN (4001-4004), datos por tabla (Geografía,
  Clientes, Contenido y Sucursales, Envíos, Viajes 497297703, Historial e Incidentes,
  Direcciones y Usuarios 2001, Catálogos 2002) y "Cambios en la base" (2003).
- Por cada script nuevo: una fila en "Cambios en la base" (N.º con apóstrofo '010, script, qué
  cambió, por qué, objetos de la base, tema del checklist) y bordes hasta la fila nueva. Si cambia
  la estructura: columnas en la pestaña de datos de la tabla (colores: PK amarillo, atributos
  celeste, FK verde, calculadas gris en cursiva con nota) y la 3FN (dependencias transitivas,
  decisiones de diseño y "RESULTADO: LAS 17 TABLAS").
- Antes de editar: cargar el skill google-workspace y leer references/sheets.md. Leer con
  get_values antes de escribir; los textos tipo fecha o "007" se convierten solos (usar apóstrofo).
- La otra planilla "Normalización Proyecto" (1LXK30TT...) quedó vieja; la vigente es logitrack_completo.

## Arquitectura (MVC)
- `modelos/<Entidad>.php`: solo SQL, heredan de `Modelo.php` (paginar con lista blanca de orden,
  ejecutar, fila). `api/<entidad>.php`: controlador (valida, llama al modelo, responde JSON).
  `paginas/<entidad>/`: vista (.php estructura + .js declarativo sobre `crud.js`).
- Base común: `config/api.php` (sesión, PDO, validaciones `campo_*`, `intentar()`, traducción de
  errores MySQL; los SIGNAL 45000 de triggers/procedimientos llegan tal cual al usuario; zona
  horaria America/Argentina/Buenos_Aires), `config/validaciones.php` (datos_envio, datos_viaje),
  `paginas/plantilla.php` (`inicio_pagina`, `fin_pagina($js, $extras)`, `con_version` = ?v=filemtime),
  `paginas/comun.js` (api(), esc(), data-hasta-ahora), `paginas/crud.js` (filtros desde la URL),
  `paginas/catalogos.js` (llenarSelect con optgroup si trae `grupo`), `api/catalogos.php`.
- Hora: la referencia es NOW() de la base (los controladores que comparan usan `SELECT NOW()`).

## Asistente "Nuevo envío" (paginas/nuevo_envio, api/asistente_envio.php)
- Estilo pedido por el usuario y a aplicar en otras pantallas: flujos guiados, pedir la menor
  cantidad de códigos posible, buscar clientes por nombre o DNI y darlos de alta en el momento.
- 4 pasos: personas (`paginas/selector_cliente.js`, reutilizable; el alta crea cliente + dirección
  en una transacción), paquete (sucursales sugeridas por provincia + "Otras en <provincia>"),
  viaje (directos, con un trasbordo, otros, crear nuevo, asignar más tarde), confirmar.
- Una sola transacción: viaje nuevo + envío (procedimiento) + 1 o 2 asignaciones (procedimiento,
  FOR UPDATE, capacidad). La dirección de entrega tiene que ser del destinatario.
- Recomendación de viajes: el usuario la quiere sugerida, no obligatoria (puede elegir cualquiera).
- Licencia especial (camión/semi) = chofer con licencia C o E.

## Diseño visual
- El usuario rediseña con Antigravity (vistas .php, plantilla, index, login). Al revisar: que no
  agregue elementos sin función (ya pasó con un buscador y una campanita) y que `hidden` no quede
  pisado por clases flex/grid (la plantilla tiene `[hidden] { display: none !important; }`).
- Tailwind, Inter y Material Symbols están en `assets/` (sin internet). No volver a los CDN.
- `login.html` es estático: subir a mano el `?v=` de login.js si cambia.
- Historial de estados inmutable en la app (API PUT/DELETE = 405); envíos tiene "Último estado" y
  "Ver historial".

## Material de la cátedra
- `base de datos 2 campus/` (no se versiona) tiene todo el campus; resumido por tema en
  `docs/teoria_campus.md`. Lo viejo del proyecto está en C:\Users\santi\Documents\LOGITRACK_archivo
  (no tocar).

## Commits recientes (rama main)
- Transacciones (017): capacidad sin carreras (FOR UPDATE + lectura con bloqueo), demo
- Seguridad parte 1 (016): cuentas por rol, permisos por rol en la app, .htaccess
- Índices (015) + prueba de 100k (database/pruebas)
- Vistas (014): las 5 originales corregidas, listados y tablero del menú
- Triggers (013): historial inmutable, auditoría, reglas en la base
- Procedimientos y funciones (012) + Detalle de viaje
- aa3b8c2 Sesiones (011)
- 5bf34ab Cambio 009: reglas de fechas en la base
- 0a8c535 Documentación: regla de la planilla y ejemplo del cambio 006
- 7b53132 Rutas de viaje y trasbordos (007, 008)
- 964852a Sucursales con su provincia (006)
- d45f151 Asistente Nuevo envío (005)
- 6923398 / 7091d5b rediseño, historial inmutable, assets locales
