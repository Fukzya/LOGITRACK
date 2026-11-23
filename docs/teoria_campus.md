# Teoría del campus aplicada a LogiTrack

Resumen del material de "base de datos 2 campus/" (Bases de Datos II, UCH 2026), ordenado por
tema del checklist. Para cada tema: qué dice la cátedra y qué espera ver en el proyecto.
Fuente entre [corchetes]: T = Topic, carpeta del campus.

Orden de trabajo acordado: CRUD → sesiones → procedimientos → triggers → vistas →
seguridad → transacciones. Normalización ya está (pestañas 0FN–3FN de la planilla).

---

## Mapa de fuentes

| Tema | Material |
|---|---|
| Programa / checklist | 00-General: Programa.pdf, CHECKLIST 2026.pdf |
| Transacciones y concurrencia | T1: Clase Inaugural (ACID, lecturas sucias, locks); T3 apunte §7; T5 |
| Normalización | T1 05/06, T2 01-03 |
| CRUD / MVC / PHP | T2: Organización del CRUD en PHP.pdf, PHP-CRUD.zip, MVC.pdf |
| Repaso 1er parcial | T2 08 (teoría: DDL/DML/TCL, ON DELETE, InnoDB, capas) |
| Seguridad | T3: BDII-SEGURIDAD (slides), apunte 07, TP2 + resolución |
| Vistas, índices, DCL | T4: El Arquitecto SQL (slides), apunte 03, práctico Jardinería (G Vistas, H Optimización) |
| Procedimientos, funciones, triggers, transacciones | T5: Lógica Avanzada (slides), apunte 2026, infografía interactiva |
| Backups | T6: Gestión de Seguridad y Backup, guía de estudio |
| Repaso 2do parcial | T6 04 (12 casos; varios mencionan LogiTrack) |

La página "Presentación de Sistemas - Casos de Estudio" (congohome.ddns.net:9000) estaba caída
el 2026-11-23: no se pudo leer la consigna oficial de LogiTrack.

## Lo que el programa promete evaluar

- Unidad I: transacciones y concurrencia, **gestión de sesiones y conexiones concurrentes**,
  arquitectura cliente-servidor / multicapa.
- Unidad II: usuarios, roles, privilegios, control de acceso, integridad y restricciones,
  privacidad, criptografía básica, calidad de datos (consistencia, completitud, validez, duplicados).
- Unidad III: DDL, restricciones, índices, subconsultas, **funciones y vistas, procedimientos,
  funciones definidas por el usuario, triggers, manejo de advertencias, errores y excepciones**.
- Unidad IV: conexiones desde la app, consultas parametrizadas, procesamiento de resultados,
  **manejo de errores** en la interacción con el SGBD.
- Unidad V: EXPLAIN, índices, optimización.
- Final: examen teórico + **defensa del proyecto integrador**.

---

## 1. CRUD [T2]

Modelo de la cátedra (PHP-CRUD.zip):
- `Conexion.php`: clase que centraliza la conexión (mysqli, charset utf8). Si cambia, se toca un archivo.
- `Usuario.php` (**Modelo**): clase con `listarTodos`, `buscarPorId`, `crear`, `actualizar`, `eliminar`.
  Todos con **sentencias preparadas** (prepare + bind_param) → evita inyección SQL.
- Archivos de vista/controlador: listar, crear (form POST), editar (form precargado), eliminar.
- MVC: Modelo = acceso a datos; Vista = HTML; Controlador = orquesta (petición → modelo → vista).
  En el ejemplo el controlador está mezclado con la vista.
- "Posibles mejoras" que la cátedra lista explícitamente:
  - validar en PHP y HTML (campos no vacíos, formato de mail, mensajes de error claros),
  - tablas HTML / CSS para presentar,
  - buscador, exportar CSV/PDF,
  - **escapar datos al mostrarlos (htmlspecialchars)**,
  - **login básico para restringir acceso al CRUD**.
- Repaso 1er parcial: arquitectura en 3 capas (presentación, negocio, datos); ON DELETE/ON UPDATE
  (RESTRICT, CASCADE, SET NULL, NO ACTION); DELETE vs TRUNCATE; UNIQUE / NOT NULL.
- Programa U-IV: consultas parametrizadas y **manejo de errores** desde la aplicación.

## 2. Sesiones [Programa U-I, T2 CRUD]

No hay apunte dedicado. Lo que aparece:
- Programa: "gestión de sesiones y conexiones concurrentes"; práctica: "configuración y
  administración de sesiones concurrentes" y "transacciones simultáneas para observar problemas
  de concurrencia (lecturas sucias, actualizaciones perdidas, bloqueos)".
- CRUD: "implementar un sistema de login básico para restringir el acceso".
- Dos sentidos para defender:
  1. **Sesión de aplicación** (PHP `$_SESSION`): login, quién es el usuario, rol, expiración, logout.
  2. **Sesión de base de datos**: cada conexión es una sesión del motor (SHOW PROCESSLIST,
     variables de sesión, nivel de aislamiento por sesión). Dos sesiones simultáneas permiten
     mostrar concurrencia (ver §7).

## 3. Procedimientos y funciones [T5]

- Procedimiento = "receta" SQL que vive en el servidor; se invoca con `CALL`; parámetros
  IN / OUT / INOUT; puede hacer DML y COMMIT/ROLLBACK; devuelve 0 o varios resultsets.
- Ventajas que remarca la cátedra: **seguridad** (el usuario de la app no necesita permisos
  sobre las tablas, solo EXECUTE), menos tráfico de red, lógica de negocio centralizada.
- Validar reglas de negocio y abortar con `SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '...'`.
- Ejemplo modelo (AcademiSys, infografía): `DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK;
  SIGNAL ... END;` + `SELECT ... FOR UPDATE` + `START TRANSACTION ... COMMIT` dentro del procedimiento.
- Función (UDF): **siempre devuelve un valor**, se usa dentro de SELECT/WHERE, solo parámetros IN,
  no hace COMMIT/ROLLBACK ni modifica tablas; declarar `DETERMINISTIC` / `READS SQL DATA`.
  Ejemplo: `calcular_antiguedad(fecha) RETURNS INT` con manejo de NULL.
- Pregunta de examen: "¿SP o función para usar en un SELECT?" → función; el SP se llama con CALL
  y puede alterar datos.
- Arquitectura de la integridad (slide final): Transacción = escudo, Procedimiento = motor,
  Función = calculadora (usada dentro del SP), Trigger = notario.

## 4. Triggers [T5, repaso 2do parcial caso 10]

- Reaccionan solos a INSERT / UPDATE / DELETE, `FOR EACH ROW`, con pseudo-registros OLD y NEW.
- **BEFORE** = validar / formatear / bloquear antes de escribir.
  **AFTER** = auditoría e historial (el cambio ya ocurrió).
- **Caso LogiTrack de la cátedra** (slides + infografía): "trazabilidad inquebrantable y auditoría
  automática de estados":
  ```sql
  CREATE TRIGGER tr_auditoria_envio_estado AFTER UPDATE ON envios FOR EACH ROW
  BEGIN
    IF OLD.estado_id <> NEW.estado_id THEN
      INSERT INTO historial_estados_envio (envio_id, estado_anterior_id, estado_nuevo_id,
                                           fecha_cambio, usuario_operador)
      VALUES (OLD.id_envio, OLD.estado_id, NEW.estado_id, NOW(), CURRENT_USER());
    END IF;
  END
  ```
  Uso: `UPDATE envios SET estado_id = 3 WHERE id_envio = 4099;` y el historial se llena solo.
- Errores típicos que toman en examen: usar BEFORE para auditoría (si el UPDATE falla queda un log
  falso) y no comparar `OLD.x <> NEW.x` (registra cambios que no ocurrieron).
- Nuestro modelo hoy: envio **no tiene estado actual**; el estado se registra insertando en
  historial_estado. Decisión pendiente para el tema triggers.

## 5. Vistas [T4, repaso caso 8]

- Tabla virtual: guarda solo la consulta (0 bytes de datos). Oculta JOINs complejos y
  **proyecta solo algunas columnas → seguridad** (ocultar datos sensibles).
- `CREATE OR REPLACE VIEW`, `DROP VIEW`, `SHOW FULL TABLES WHERE Table_type = 'VIEW'`.
- Actualizable solo si incluye todas las columnas NOT NULL de la tabla base y no usa
  agregados (SUM, MAX...), GROUP BY ni DISTINCT. Vistas con GROUP BY = solo lectura/reportes.
- Caso de examen: auditor que no puede ver salarios → vista con columnas permitidas +
  `REVOKE ALL ON tabla` + `GRANT SELECT ON vista`.
- Práctico Jardinería G: vistas de listado clientes+pagos y clientes+pedidos con totales.

## 6. Seguridad [T3, T4 §5, T6, repaso casos 6, 8, 11]

- Tríada **CIA**: Confidencialidad (control de acceso, cifrado), Integridad (constraints,
  checksums), Disponibilidad (backups, replicación).
- **Mínimo privilegio**; **separación de funciones** (cuentas DDL ≠ cuentas DML; desarrollo ≠
  producción); **prohibido usar cuentas genéricas root/admin** para la app (trazabilidad).
- Usuarios `'user'@'host'` (restringir host, sin comodines), **roles** (RBAC = estándar de la
  industria; DAC = el dueño decide; MAC = etiquetas). Privilegios de sistema vs de objeto
  (SELECT sobre tabla, EXECUTE sobre SP). GRANT / REVOKE, `WITH GRANT OPTION` (cuidado: cascada),
  `FLUSH PRIVILEGES`, auditar con `SHOW GRANTS`. Nunca `ALL` a cuentas de aplicación.
- TP2 (resolución): `CREATE USER 'auditor_externo'@'localhost' ...; GRANT SELECT ON db.* ...`;
  `CREATE ROLE 'operador_ventas'; GRANT INSERT, UPDATE ON db.Pedidos TO ...` (sin DELETE = denegado);
  `ALTER TABLE ... ADD CONSTRAINT chk_precio CHECK (precio > 0)`; email `NOT NULL UNIQUE`;
  deduplicación (mismo CUIT con distintas direcciones = falla de consistencia / duplicación).
- Integridad: de **entidad** (PK), **referencial** (FK + ON DELETE/UPDATE), de **dominio**
  (tipos, CHECK, DEFAULT). Declarativa (en la estructura) vs procedural (triggers) cuando la
  regla cruza tablas o depende del tiempo.
- Privacidad (Ley 25.326): **masking** (mostrar parte), anonimización (irreversible),
  pseudonimización (reversible con llave).
- Criptografía: hashing + **salt** para contraseñas (irreversible); cifrado **en tránsito**
  (SSL/TLS entre PHP y MySQL, contra sniffing); **en reposo** (TDE, contra robo del disco/.ibd).
- Calidad de datos: consistencia, completitud, validez (formatos/regex), control de duplicados.
- Backups [T6]: frío vs caliente; físico (.ibd) vs lógico (mysqldump); **`mysqldump --routines
  --triggers`** (sin esas banderas se pierden SP, funciones y triggers); regla 3-2-1; automatizar
  (cron / Programador de tareas); probar restauraciones. Caso LogiTrack: restauración en caliente
  de historial_estado.

## 7. Transacciones y concurrencia [T1, T3 §7, T5, repaso caso 7]

- Transacción = unidad lógica de trabajo, **todo o nada**. ACID: Atomicidad, Consistencia,
  Aislamiento, Durabilidad. `START TRANSACTION` → DML → `COMMIT` o `ROLLBACK`.
- Problemas: **lectura sucia** (leer algo no confirmado que después se deshace),
  **actualización perdida** (dos escriben y uno pisa al otro), bloqueos.
- Control: **locks** (`SELECT ... FOR UPDATE`), niveles de aislamiento (InnoDB usa
  REPEATABLE READ por defecto; MVCC).
- Casos de la cátedra: MediTurnos (reservar turno + registrar pago juntos), AcademiSys (último cupo
  con FOR UPDATE), "la última entrada del concierto" (clase inaugural).
- En un SP: `DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; ... END;`.

## 8. Índices y optimización [T4, repaso casos 5 y 12]

- B-Tree (por defecto en InnoDB, igualdad y rangos), Hash (igualdad), FULLTEXT
  (MATCH ... AGAINST), R-Tree (espacial), funcionales (MySQL 8.0.13+; MariaDB no).
- Índices aceleran lecturas y penalizan escrituras: no indexar todo.
- `EXPLAIN`: `type = ALL` y `key = NULL` → full table scan (mal); `ref`/`range`/`const` → bien;
  `rows` = filas examinadas. Ciclo: abstraer → vista → EXPLAIN → índice → verificar.
- `ANALYZE TABLE` (estadísticas), `OPTIMIZE TABLE` (desfragmentar tras borrados masivos).
- Evitar funciones sobre la columna en el WHERE (`YEAR(fecha) = 2008` no usa índice;
  `fecha BETWEEN ...` sí).
- Consultas: COUNT(columna) y no COUNT(*) en LEFT JOIN ("ghost count"); FULL OUTER JOIN se simula
  con UNION; NOT IN con NULL no devuelve filas; EXISTS corta en la primera coincidencia.
