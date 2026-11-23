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

## Pendientes
1. Historial duplicado: `procedimiento_crear_envio` y `trigger_after_insert_envio` insertan ambos
   "En sucursal origen" al crear un envío (en los datos se ve en los envíos 8015-8018).
2. En el volcado `operador1` tiene rol admin.
3. Vistas (5) y procedimientos `registrar_estado_envio` / `resumen_por_sucursal` no los usa el código PHP.
