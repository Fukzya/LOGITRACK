# Transacciones y concurrencia: resumen para la defensa

## Qué es una transacción

Un grupo de operaciones que se hace **todo o nada**: `START TRANSACTION` → cambios → `COMMIT`
(confirmar) o `ROLLBACK` (deshacer como si nada hubiera pasado).

| ACID | Qué garantiza | Ejemplo en LogiTrack |
|---|---|---|
| **A**tomicidad | Todo o nada | El asistente no deja un viaje vacío ni un envío sin su viaje |
| **C**onsistencia | Se cumplen las reglas al terminar | Un viaje nunca lleva más kg que la capacidad de su vehículo |
| A**i**slamiento | Las transacciones simultáneas no se pisan | Dos operadores cargando la misma moto (ver abajo) |
| **D**urabilidad | Lo confirmado no se pierde | InnoDB escribe en su log antes de responder al COMMIT |

**Toda sentencia es una transacción.** Con `autocommit = 1`, un `UPDATE` suelto es una transacción
de una sola sentencia: si su trigger hace `SIGNAL`, se deshace todo (trigger incluido). Las
transacciones **explícitas** agrupan varias sentencias que tienen que salir juntas.

## Nuestras 6 transacciones explícitas (las dos capas)

| # | Dónde | Todo o nada | Capa |
|---|---|---|---|
| 1 | Asistente "Nuevo envío" | Viaje nuevo (opcional) + envío + 1 o 2 asignaciones | PHP (PDO) |
| 2 | Detalle de viaje → "Agregar envíos" | Varias asignaciones a un viaje | PHP |
| 3 | Viaje completo | Viaje nuevo + sus asignaciones | PHP |
| 4 | Alta de cliente (asistente) | Cliente + su dirección | PHP |
| 5 | Alta de envío | Envío + estado RECIBIDO (trigger). Si lo llama el asistente, se suma a la del asistente | PHP |
| 6 | `procedimiento_cerrar_viaje` | Llegada del viaje + "En sucursal" para cada envío | **Base** |

**En la aplicación** (asistente):
```php
$pdo->beginTransaction();
try { ...crear viaje, envío, asignaciones...; $pdo->commit(); }
catch (Throwable $t) { $pdo->rollBack(); throw $t; }
```
**En la base** (`procedimiento_cerrar_viaje`):
```sql
DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
START TRANSACTION;
SELECT ... FROM viaje WHERE viaje_id = p_viaje FOR UPDATE;
UPDATE viaje ...;  INSERT INTO historial_estado ... (uno por envío);
COMMIT;
```
Si un envío falla, el EXIT HANDLER hace ROLLBACK: no queda un viaje cerrado con envíos sin actualizar.

## El problema que encontramos: la moto de 50 kg con 60 kg

Probado con dos sesiones a la vez (`database/pruebas/demo_concurrencia.sh`). Moto con capacidad
50 kg, dos envíos de 30 kg. Es el caso de la cátedra "la última entrada del concierto":

```
Operador A                          Operador B
lee carga: 0 kg -> "entra"          lee carga: 0 kg -> "entra"
asigna 30 kg                        asigna 30 kg
COMMIT                              COMMIT        -> 60 kg en una moto de 50
```

Esto es una **actualización perdida**: cada uno decidió con un dato que ya no era cierto.

**Y con `FOR UPDATE` también fallaba** (camino del asistente). La causa es el aislamiento:

| Nivel | Lectura sucia (ver algo sin COMMIT) | Ver lo que otro confirmó en el medio |
|---|---|---|
| READ UNCOMMITTED | Sí | Sí |
| READ COMMITTED | No | Sí (cada lectura, foto nueva) |
| **REPEATABLE READ** (MariaDB, el nuestro) | No | **No**: la foto es la de la PRIMERA lectura |
| SERIALIZABLE | No | Todo en fila |

InnoDB usa **MVCC**: cada transacción lee una "foto" de los datos. El asistente creaba primero el
envío (al hacerlo, leía, y eso sacaba la foto) y después bloqueaba el viaje. Cuando conseguía el
bloqueo, seguía mirando la foto vieja: carga = 0.

**La regla:** bloquear primero y leer después, o **leer con bloqueo** (`FOR UPDATE` /
`LOCK IN SHARE MODE`): una lectura con bloqueo siempre ve el último dato confirmado.

## El arreglo (script 017)

| Dónde | Qué |
|---|---|
| `trigger_before_insert_viaje_envio` | Lee el viaje con `FOR UPDATE`: si otro está asignando al mismo viaje, **espera** a que termine |
| `funcion_carga_viaje_bloqueando` | La suma de kg con `LOCK IN SHARE MODE`: ve lo confirmado aunque la transacción tenga una foto vieja |
| `trigger_before_update_viaje` | Al cambiar el vehículo, también controla con la carga leída con bloqueo |
| Asistente (PHP) | Solo abre y cierra la transacción. El bloqueo lo hace el trigger y la carga se lee con bloqueo, así que da igual que el envío se cree antes (cambio 020: antes el PHP bloqueaba y controlaba la capacidad, repitiendo la regla) |
| `funcion_carga_viaje` | Sin cambios: la usan vistas y listados, que solo muestran |

Va **en la base**: protege las pantallas, el asistente y cualquier programa (HeidiSQL incluido).
Costo medido: asignar tarda ~1 ms (es un bloqueo de una fila por clave primaria).

**Alternativa que no elegimos:** cambiar la app a READ COMMITTED. Arregla la foto vieja, pero solo
para las conexiones de la app; otro programa en REPEATABLE READ seguiría pudiendo pasarse.

**Control de concurrencia "gratis" con una restricción:** el `UNIQUE (nro_envio, tramo)` impide
que dos asignaciones simultáneas del mismo envío queden las dos como tramo 1: la segunda falla.

## Bloqueos que esperan y deadlocks

- **Espera:** si otra transacción tiene la fila bloqueada, se espera. MariaDB espera 50 s de fábrica;
  la app usa 10 s (`innodb_lock_wait_timeout` en `config/conexion.php`). Después: error **1205** →
  la app responde *"Otro usuario está modificando ese dato. Probá de nuevo en unos segundos."*
- **Deadlock (abrazo mortal):** A bloqueó el viaje 1 y espera el 2; B bloqueó el 2 y espera el 1.
  MariaDB lo detecta, cancela a uno (ROLLBACK, error **1213**) y el otro sigue. La app responde
  *"La operación chocó con otra que se hacía al mismo tiempo y se deshizo. Probá de nuevo."*
  Para evitarlos: bloquear siempre en el mismo orden (el asistente bloquea tramo 1 y después tramo 2).

## Resultados medidos (25/11/2026)

| Prueba | Antes del 017 | Después |
|---|---|---|
| Dos asignaciones simultáneas a la moto (mysql) | 60 kg | La segunda se rechaza |
| Ídem, B con foto previa + FOR UPDATE | 60 kg | B espera el COMMIT de A y se rechaza |
| Dos asistentes a la vez por la API | — | 201 y *"quedan 20,00 kg libres"*; el envío rechazado no existe (ROLLBACK) |
| Dos "Asignar envío" a la vez por la API | — | 201 y *"no tiene capacidad"* |
| Viaje bloqueado desde otra sesión | — | 409 a los 10 s |

Desde el 020 el mensaje de capacidad lo arma el trigger: *"El viaje 3095 no tiene lugar: quedan 1.743,58 kg libres y el envío pesa 2.000,00 kg."*

## Demo en vivo

**Opción rápida:** `bash database/pruebas/demo_concurrencia.sh` (crea una moto de prueba, corre las
dos carreras y borra lo que creó).

**Con dos pestañas de HeidiSQL** (pestaña A y pestaña B, mismo viaje futuro, por ejemplo uno de
`vista_viajes_programados`):

1. **FOR UPDATE espera.** A: `START TRANSACTION; SELECT * FROM viaje WHERE viaje_id = X FOR UPDATE;`
   B: lo mismo → **B queda esperando**. A: `COMMIT;` → B sigue enseguida.
2. **No hay lecturas sucias.** A: `START TRANSACTION; UPDATE envio SET peso_kg = 99 WHERE nro_envio = N;`
   B: `SELECT peso_kg FROM envio WHERE nro_envio = N;` → ve el peso viejo. A: `ROLLBACK;` → nunca existió.
3. **La foto de REPEATABLE READ.** B: `START TRANSACTION; SELECT peso_kg FROM envio WHERE nro_envio = N;`
   A (sin transacción): `UPDATE envio SET peso_kg = peso_kg + 1 WHERE nro_envio = N;`
   B: el mismo SELECT → **sigue viendo el valor viejo**. B: `SELECT ... LOCK IN SHARE MODE;` → ve el
   nuevo. B: `COMMIT;` y un SELECT normal ya ve el nuevo. (Devolver el peso con `- 1` al terminar;
   los dos cambios quedan en la auditoría, que es justamente lo que tiene que pasar.)

## Preguntas probables

- **¿Por qué no alcanza con el control en PHP?** Porque PHP decidía con una foto vieja, y porque
  otro camino (otra pantalla, HeidiSQL) no pasaba por ese control. El trigger es el único lugar por
  donde pasan todas las asignaciones.
- **¿FOR UPDATE bloquea toda la tabla?** No: InnoDB bloquea **filas** (la del viaje, por su clave
  primaria). Asignar a otro viaje no espera.
- **¿Por qué la función con bloqueo no reemplaza a la común?** Las vistas y listados leen cientos de
  viajes: bloquear todo eso para mostrar una tabla frenaría a los que asignan.
- **¿Qué pasa con la transacción si el trigger hace SIGNAL?** Falla esa sentencia; PHP hace ROLLBACK de
  toda la transacción (por ejemplo, del asistente: no queda ni el envío ni el viaje nuevo).
- **¿Por qué `cerrar_viaje` tiene la transacción adentro y el asistente afuera?** Para mostrar las dos
  formas. Ojo: un procedimiento con `START TRANSACTION` no se puede llamar dentro de otra
  transacción abierta (`START TRANSACTION` confirma la anterior implícitamente).
