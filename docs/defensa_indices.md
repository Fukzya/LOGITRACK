# Índices y EXPLAIN: resumen para la defensa

## Qué es un índice

Una estructura aparte, **ordenada**, que apunta a las filas de la tabla. Es como el índice
alfabético de un libro: para encontrar "trasbordo" no leés las 300 páginas, vas a la T.

- InnoDB usa **B-Tree** (árbol balanceado): sirve para igualdad (`=`), rangos (`BETWEEN`, `>`,
  `<`), `ORDER BY` y prefijos (`LIKE 'gonz%'`). Otros tipos: Hash (solo igualdad), FULLTEXT
  (`MATCH ... AGAINST`), R-Tree (espacial).
- **No cambia ningún resultado**, solo la velocidad. Se crea y se borra sin tocar los datos.
- **Costo:** ocupa espacio y cada INSERT/UPDATE/DELETE tiene que actualizarlo. Por eso **no se
  indexa todo**: solo lo que las consultas reales buscan, filtran u ordenan.

## Cómo se lee EXPLAIN

`EXPLAIN SELECT ...` muestra **cómo** piensa ejecutar la consulta, sin ejecutarla. En MariaDB,
`ANALYZE SELECT ...` la ejecuta y agrega lo que pasó de verdad (`r_rows`).

| Columna | Qué mirar |
|---|---|
| `type` | Cómo accede, de mejor a peor: `const` (1 fila por PK) → `eq_ref` → `ref` (igualdad en un índice) → `range` (rango) → `index` (recorre todo el índice) → **`ALL` (recorre toda la tabla: mal)** |
| `key` | El índice que usó. `NULL` = ninguno |
| `rows` / `r_rows` | Filas que estima leer / que leyó de verdad |
| `Extra` | **`Using filesort`** = tuvo que ordenar aparte (malo con muchas filas). `Using index` = resolvió todo con el índice, sin ir a la tabla (bueno) |

**Ciclo de trabajo de la cátedra:** consulta (o vista) → EXPLAIN → índice → EXPLAIN de nuevo → medir.

## Nuestros índices

**Automáticos (los crea la estructura):** cada PRIMARY KEY, cada UNIQUE (`usuario.username`,
`viaje_envio (nro_envio, tramo)`, `sesion.token_hash`...) y cada FOREIGN KEY (InnoDB exige un
índice en la columna de la FK para controlarla rápido: `fk_envio_suc_orig`, `fk_viaje_chofer`...).

**Creados a propósito:**

| Índice | Tabla (columnas) | Para qué consulta | Script |
|---|---|---|---|
| `idx_historial_envio_fecha` | historial_estado (nro_envio, fecha_hora, id_historial) | Último estado de un envío (`funcion_ultimo_estado`, pendientes, triggers) | 015 |
| `idx_envio_fecha` | envio (fecha_envio) | Filtro por fecha de Envíos, tablero, resumen | 015 |
| `idx_viaje_salida` | viaje (fecha_salida) | Viajes programados, en curso, próximos 7 días | 015 |
| `idx_envio_remitente` / `_destinatario` | envio | Envíos de un cliente (asistente, vista de clientes) | volcado |
| `idx_viaje_patente` | viaje (vehiculo_patente) | ¿El vehículo ya tiene viaje ese día? (trigger) | volcado |
| `idx_auditoria_registro` / `_fecha` | auditoria | "Ver cambios" de un envío o viaje; listado por fecha | 013 |
| `idx_sesion_usuario_fin` | sesion (usuario_id, fin) | Sesiones abiertas de un usuario (trigger de usuario) | 011 |

## Antes y después (EXPLAIN real, base de la PC: 8.130 envíos, 30.475 estados, 3.231 viajes)

Para comparar se usó `IGNORE INDEX (...)`, que le pide a MariaDB que haga de cuenta que el índice
no existe.

**1. Último estado de un envío** (el envío 2749, que tiene 6 estados):
```sql
SELECT estado FROM historial_estado WHERE nro_envio = 2749
 ORDER BY fecha_hora DESC, id_historial DESC LIMIT 1;
```
| | type | key | Extra |
|---|---|---|---|
| Con `idx_historial_envio_fecha` | ref | idx_historial_envio_fecha | Using where |
| Solo con el índice de nro_envio | ref | idx_historial_envio | Using where; **Using filesort** |
| Sin ninguno de los dos | index | idx_historial_fecha | lee **16.054 filas** para encontrar 1 |

Con el índice compuesto, los estados de cada envío ya están **ordenados por fecha dentro del
índice**: lee el último y listo. Esta consulta se hace una vez por cada envío en la vista de
pendientes, el tablero y los triggers: con 100.000 envíos, el filesort se repetía 100.000 veces.

**2. Envíos de noviembre** (filtro por fecha de la pantalla Envíos):
| | type | key | rows leídas |
|---|---|---|---|
| Con `idx_envio_fecha` | **range** | idx_envio_fecha | 83 |
| Sin | **ALL** | NULL | 8.089 (todas) |

**3. Viajes programados** (`fecha_salida > NOW()`):
| | type | rows leídas |
|---|---|---|
| Con `idx_viaje_salida` | range | 187 |
| Sin | ALL | 3.231 (todas) |

**4. Por clave primaria** (`WHERE nro_envio = 8113`): `type = const`, 1 fila. Lo mejor posible.

## Medido con 100.000 envíos (copia de la base, `database/pruebas/carga_100k.sql`)

| Qué | Antes | Después |
|---|---|---|
| Memoria de MariaDB (`innodb_buffer_pool_size` 16 MB → 2 GB, my.ini) | 1.ª página de Envíos: 31 s | 1,9 s |
| Índices del 015 | Tablero: 3,2 s · filtro por fecha: 368 ms | 1,8 s · 182 ms |
| `Modelo::paginarPorClave` (elegir las 50 claves en la tabla y después traer esas filas de la vista) | Envíos 1,85 s · Historial 0,9 s | 95 ms · 104 ms |

**Lección:** lo que más pesó fue la memoria. Con 16 MB, InnoDB no podía tener los índices en RAM
y leía el disco todo el tiempo; un índice ayuda poco si no entra en memoria.

## Errores típicos (con ejemplos nuestros)

| Error | Ejemplo | EXPLAIN | Cómo se escribe bien |
|---|---|---|---|
| **Función sobre la columna** en el WHERE | `WHERE YEAR(fecha_envio) = 2026 AND MONTH(fecha_envio) = 11` | `type = index`: recorre las 8.089 entradas del índice | `WHERE fecha_envio BETWEEN '2026-11-01' AND '2026-11-30'` → `range`, 83 |
| `LIKE` con `%` adelante | Buscar cliente `apellido LIKE '%gonz%'` | `ALL` (5.001 filas) | Un índice B-Tree solo sirve para `'gonz%'` (prefijo). Para buscar "en el medio": FULLTEXT |
| Función en un JOIN | `JOIN ... ON col = funcion()` | Calcula la función por cada fila | Visto en procedimientos: el nombre del estado se completa en PHP |

**En nuestro propio trigger** (¿el vehículo ya tiene viaje ese día?) hay un `DATE(fecha_salida) = ...`.
No es un problema: MariaDB entra primero por `idx_viaje_patente` (35 viajes de ese vehículo) y
recién ahí aplica el `DATE()`. La función solo molesta cuando es la **única** forma de filtrar.

## Regla del prefijo izquierdo (y un hallazgo)

Un índice compuesto `(a, b, c)` sirve para buscar por `a`, por `a, b` y por `a, b, c`, pero **no**
por `b` sola. Es como una guía telefónica ordenada por apellido y nombre: sirve para buscar
"González", no para buscar a todos los "Juan".

Por eso hay **dos índices redundantes** (heredados del volcado original):
- `idx_historial_envio (nro_envio)`: ya lo cubre `idx_historial_envio_fecha (nro_envio, ...)`.
- `fk_ve_envio (nro_envio)` en viaje_envio: ya lo cubre el UNIQUE `(nro_envio, tramo)`.

No dan resultados incorrectos, pero cada INSERT en historial y en viaje_envio actualiza un índice
de más. Se pueden borrar (la FK sigue funcionando con el compuesto).

## Mantenimiento

- `ANALYZE TABLE envio;` actualiza las estadísticas con las que el optimizador elige índice
  (después de cargas grandes, como el script de 100.000).
- `OPTIMIZE TABLE historial_estado;` reorganiza la tabla y sus índices después de borrados masivos.
- `SHOW INDEX FROM envio;` lista los índices de una tabla.

## Demo en vivo (HeidiSQL)

```sql
-- 1) Bien: range con 83 filas
EXPLAIN SELECT nro_envio FROM envio WHERE fecha_envio BETWEEN '2026-11-01' AND '2026-11-30';
-- 2) Lo mismo con función sobre la columna: ya no puede buscar en el índice
EXPLAIN SELECT nro_envio FROM envio WHERE YEAR(fecha_envio) = 2026 AND MONTH(fecha_envio) = 11;
-- 3) El filesort del último estado, con y sin el índice compuesto
EXPLAIN SELECT estado FROM historial_estado WHERE nro_envio = 2749 ORDER BY fecha_hora DESC, id_historial DESC LIMIT 1;
EXPLAIN SELECT estado FROM historial_estado IGNORE INDEX (idx_historial_envio_fecha)
         WHERE nro_envio = 2749 ORDER BY fecha_hora DESC, id_historial DESC LIMIT 1;
```

## Preguntas probables

- **¿Por qué no poner un índice en cada columna?** Cada índice se actualiza en cada escritura y ocupa
  espacio. Se indexa lo que las consultas reales buscan; el resto, no.
- **¿Por qué el índice del historial tiene 3 columnas?** `nro_envio` para encontrar los estados del
  envío, `fecha_hora` para que ya estén ordenados, `id_historial` para desempatar dos estados con la
  misma hora. Así no hay filesort.
- **¿Una PK es un índice?** Sí. En InnoDB, además, la tabla está **guardada en el orden de la PK**
  (índice agrupado o *clustered*): por eso buscar por PK es `const`.
- **¿El índice lo elige uno?** No: lo elige el optimizador con las estadísticas. Se puede forzar
  (`FORCE INDEX`) o prohibir (`IGNORE INDEX`), pero casi nunca hace falta; lo usamos para comparar.
- **¿Por qué no hicieron más arreglos con 100.000?** El script de 100.000 lo traen los profesores y
  no lo conocemos: se hizo lo de mayor efecto y menor riesgo (memoria, 3 índices, paginar por clave).
  Precalcular el estado actual con triggers quedó descartado: cambia mucho y los triggers podrían
  rechazar datos de un script ajeno.
