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
  numerado en `database/cambios/` (el próximo es 010_...), con un encabezado que explica qué y por
  qué, y se corre con `Get-Content -Raw -Encoding UTF8 script.sql | mysql -uroot
  --default-character-set=utf8mb4 logitrack` (mysql en c:\xampp\mysql\bin).
- Cada cambio de la base se documenta TAMBIÉN en la planilla (ver "Planilla").
- Antes de proponer, analizar los datos reales de la base (consultas) y decir lo que no cierra.
- Probar todo antes de entregar: API con curl (bash, cookie jar en el scratchpad) y pantallas con
  Playwright. Para la sesión se crea un usuario temporal `zz_prueba_op` (password_hash con php -r)
  y se borra al terminar, junto con los datos de prueba (los rechazos de transacciones dejan
  huecos en los AUTO_INCREMENT: es normal). Borrar la carpeta `.playwright-mcp/` antes del commit.
- Si el usuario no está de acuerdo con algo o no lo entiende, explicarle con ejemplos concretos y
  recomendar una opción; no implementar decisiones de diseño grandes sin su OK.

## Forma de trabajo por tema del checklist
Orden acordado: CRUD → sesiones → procedimientos → triggers → vistas → seguridad → transacciones
(+ índices/EXPLAIN). Para cada tema:
1. Análisis: qué está hecho y qué falta según la teoría (`docs/teoria_campus.md`, NO releer los PDF
   de `base de datos 2 campus/`) y según el código/base reales.
2. Plan propuesto. Esperar la aprobación antes de implementar.
3. Implementar, probar, commit/push, actualizar planilla y este archivo.
4. Resumen corto y didáctico para la defensa: qué es, de qué se encarga, dónde y cómo lo aplicamos.

## Estado del checklist (2026-11-23)
- Normalización: hecha en la planilla (0FN a 3FN), 17 tablas + USUARIO. Actualizada con 007.
- CRUD: terminado (MVC, 15 entidades).
- Sesiones: PRÓXIMO TEMA. Hoy hay login básico (config/session.php con timeout, api/login.php,
  check_session, logout). Falta analizar contra la teoría (vencimiento, regeneración de id,
  conexiones concurrentes en la base, etc.).
- Procedimientos: se usan procedimiento_crear_envio, procedimiento_asignar_envio_a_viaje y
  procedimiento_buscar_rutas (asistente). Faltan pantallas para procedimiento_cerrar_viaje
  (api/cerrar_viaje.php sin uso, estilo viejo) y registrar_estado_envio / resumen_por_sucursal
  (sin uso). Idea acordada: botón "Cerrar viaje", "Cambiar estado" desde la fila del envío,
  "Detalle de viaje" con "Agregar envíos pendientes".
- Triggers: muchos ya hechos (ver 007 y 009). Falta: historial inmutable también en la base
  (trigger que rechace UPDATE/DELETE en historial_estado; hoy solo lo bloquea la API con 405).
- Vistas: en uso vista_sucursal_ubicacion y vista_viajes_programados. Las 5 vistas originales
  (vista_clientes_con_envios, vista_envios_detalle, vista_envios_pendientes,
  vista_historial_completo, vista_viajes_activos) no las usa el código. Idea: tarjetas en el menú.
- Seguridad: pendiente (el usuario avisó que es extenso). Notas: la app usa root sin contraseña,
  CORS `*` en config/db.php, operadores pueden escribir todo (faltan permisos por rol),
  calidad de datos (direcciones de entrega de terceros en los datos históricos).
- Transacciones: el asistente y viaje_completo usan transacción + FOR UPDATE; falta llevarlo a
  procedimientos con EXIT HANDLER y explicarlo.

## Pendiente inmediato
- Propuesto y sin respuesta: script 010 de limpieza de datos de prueba del usuario que quedaron
  fuera de las reglas de 009: envíos 8019, 8104 y 8110 con fecha_envio 2026-11-24 (futura),
  estado 31332 del envío 8110 con fecha 22/11 (anterior a su RECIBIDO) y viaje 3001 con
  llegada real futura. Después: análisis de sesiones.
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
- Los datos de prueba originales (2024) se generaron al azar y NO cumplen las reglas nuevas
  (envíos con hasta 9 viajes, estados antes de la fecha del envío, etc.). No se corrigen: los
  triggers controlan lo nuevo. Decirlo así en la defensa.
- Sugerido al usuario y pendiente: regenerar database/logitrack.sql desde HeidiSQL con
  `--routines --triggers` para que el volcado incluya 002-009.

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
- 5bf34ab Cambio 009: reglas de fechas en la base
- 0a8c535 Documentación: regla de la planilla y ejemplo del cambio 006
- 7b53132 Rutas de viaje y trasbordos (007, 008)
- 964852a Sucursales con su provincia (006)
- d45f151 Asistente Nuevo envío (005)
- 6923398 / 7091d5b rediseño, historial inmutable, assets locales
