# LogiTrack

Sistema de gestión de envíos y viajes, trabajo práctico de Base de Datos 2.
Es PHP con PDO y MariaDB, y corre sobre XAMPP.

## Estructura

```
api/          Endpoints PHP que devuelven JSON (uno por entidad)
config/       db.php (conexión) y session.php (login / sesiones)
paginas/      Una carpeta por módulo: x.php (página) + x.js (lógica del front)
database/
  logitrack.sql   Volcado completo de la base: tablas, datos, procedimientos, triggers y vistas
  cambios/        Scripts que modifican la base, numerados y en orden
index.php     Menú principal (requiere sesión)
login.html    Pantalla de login
```

## Instalación

1. Clonar el repo dentro de `C:\xampp\htdocs\` para que quede en `C:\xampp\htdocs\LOGITRACK`.
2. Levantar Apache y MySQL desde el panel de XAMPP.
3. En HeidiSQL, importar `database/logitrack.sql`. Crea la base `logitrack` con todo.
4. Ejecutar en orden los scripts de `database/cambios/`.
5. Abrir http://localhost/LOGITRACK/

La conexión usa `root` sin contraseña. Se cambia en `config/db.php`.

## Cambios a la base

Cada cambio de estructura va como un archivo nuevo en `database/cambios/`,
con número correlativo (`002_...sql`, `003_...sql`). No se edita un script
que ya se ejecutó: si hay que corregir algo, se agrega uno nuevo.

Cada tanto conviene regenerar `database/logitrack.sql` desde HeidiSQL
(Exportar base de datos como SQL) para que el volcado quede al día.

## Pendientes conocidos

- [ ] **Historial duplicado al crear un envío**: `procedimiento_crear_envio` y
      `trigger_after_insert_envio` insertan los dos el estado "En sucursal origen".
- [ ] Usuarios: el usuario `operador1` tiene rol `admin` en el volcado.
