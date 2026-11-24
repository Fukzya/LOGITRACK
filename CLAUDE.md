# LogiTrack - contexto para Claude

TP de la materia Base de Datos 2. PHP (PDO) + MariaDB 10.4 sobre XAMPP, front con JS (fetch).
Ver README.md para la estructura y la instalación.

## Reglas de trabajo
- Commits y PRs SIN línea `Co-Authored-By` ni atribución a Claude (pedido explícito del usuario).
- Repo: https://github.com/Fukzya/LOGITRACK (rama main). El usuario trabaja en una PC y una notebook:
  hacer `git pull` antes de empezar y `git push` al terminar.
- Cambios a la base: nunca editar `database/logitrack.sql` a mano. Cada cambio va como un script nuevo
  numerado en `database/cambios/` (002_..., 003_...).
- Responder en español.

## Estado (2026-11-23)
- Se unificaron las versiones viejas en esta carpeta. Lo viejo está archivado en
  C:\Users\santi\Documents\LOGITRACK_archivo (no tocar).
- Base local de la PC: importada desde database/logitrack.sql + cambio 001 aplicado y verificado.
- La base de la notebook podría tener objetos posteriores al volcado (3/6 10:02). Pendiente re-exportarla.

## Resuelto
- Cambio 002 (aplicado y verificado en la PC el 2026-11-23): `procedimiento_crear_envio` ya no inserta
  en `historial_estado` (lo hace solo `trigger_after_insert_envio`), se borraron los duplicados
  (envíos 8015-8018) y `operador1` pasó a rol operador. Falta correrlo en la notebook.
- Cambio 003 (aplicado en la PC el 2026-11-23): tablas catálogo `estado_envio`, `tipo_incidente`,
  `tipo_licencia` con FK desde historial_estado.estado, incidente.tipo_incidente y
  chofer.chofer_tipo_licencia. Estados unificados a códigos (RECIBIDO, EN_TRANSITO, ...).
  Los <select> del front se llenan desde `api/catalogos.php`.
- Cambio 004 (aplicado en la PC): 1773 mails de clientes tenían tildes (inválidos); se normalizaron.

## CRUD en MVC (terminado el 2026-11-23, opción B)
- Las 15 entidades: `modelos/<Entidad>.php` (solo SQL, heredan de `Modelo.php`) +
  `api/<entidad>.php` (controlador) + `paginas/<entidad>/` (vista declarativa sobre `crud.js`).
- Base común: `config/api.php` (validaciones `campo_*`, `intentar()` para mensajes de FK/duplicados,
  traducción de errores MySQL, nunca muestra detalles internos), `paginas/plantilla.php`,
  `paginas/comun.js`, `paginas/crud.js`, `paginas/catalogos.js` + `api/catalogos.php`.
- Probado: 103 pruebas de API + las 15 pantallas en navegador.
- Para probar con sesión se crean usuarios temporales `zz_prueba_*` y se borran al terminar.
- Pendiente para temas siguientes: `api/cerrar_viaje.php` y `api/viaje_completo.php` no los usa
  ninguna pantalla (procedimientos); los permisos de escritura por rol (seguridad);
  la dirección de entrega de los envíos es aleatoria en los datos (calidad de datos).

## Diseño visual (Antigravity, revisado el 2026-11-23)
- El usuario rediseña el front con Antigravity (solo vistas .php, plantilla, index, login).
  Al revisar sus cambios: verificar que no agregue elementos sin función (ya pasó con un
  buscador y una campanita) y que `hidden` no quede pisado por clases flex/grid (la plantilla
  tiene `[hidden] { display: none !important; }`).
- Tailwind, Inter y Material Symbols están en `assets/` (sin internet). No volver a los CDN.
- Los scripts de las pantallas se cargan con `?v=filemtime` (`con_version()` en plantilla.php);
  `login.html` es estático: subir a mano el `?v=` de login.js si cambia.
- Historial de estados INMUTABLE en la app: la API rechaza PUT/DELETE (405). Pendiente para el
  tema triggers: protegerlo también en la base (trigger que rechace UPDATE/DELETE).

## Material de la cátedra
- `base de datos 2 campus/` (no se versiona) tiene todo el campus. Ya está resumido por tema en
  `docs/teoria_campus.md`: leer ese archivo en lugar de los PDF.
- Orden de trabajo del checklist: CRUD → sesiones → procedimientos → triggers → vistas →
  seguridad → transacciones. Para cada tema: análisis hecho/falta según la teoría, plan, y un
  resumen corto y didáctico para la defensa.

## Pendientes
1. Vistas (5) y procedimientos `registrar_estado_envio` / `resumen_por_sucursal` no los usa el código PHP.
