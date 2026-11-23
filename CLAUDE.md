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

## Pendientes
1. Vistas (5) y procedimientos `registrar_estado_envio` / `resumen_por_sucursal` no los usa el código PHP.
