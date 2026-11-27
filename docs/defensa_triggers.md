# Triggers: resumen para la defensa

## Qué es un trigger

Código que la base ejecuta **sola** cuando pasa un INSERT, UPDATE o DELETE en una tabla, una vez
por cada fila (`FOR EACH ROW`). Ve la fila con `OLD` (como estaba) y `NEW` (como va a quedar).
Nadie lo llama: por eso vale para cualquier programa que escriba en la base (la app, HeidiSQL, un
script), no solo para la app.

| | BEFORE: el "portero" | AFTER: el "notario" |
|---|---|---|
| Cuándo | Antes de escribir | Cuando el cambio ya está hecho |
| Para qué | Validar y rechazar (`SIGNAL`), o corregir `NEW` | Registrar lo que pasó (historial, auditoría) |
| Ejemplo nuestro | Camión con chofer de licencia B → *"Ese vehículo pide licencia especial y el chofer tiene licencia B"* y no se guarda | Cambian el peso de un envío de 12.50 a 13.00 kg → fila en `auditoria`: 12.50 → 13.00, operador1, fecha |

**Errores típicos de examen:**
1. Auditar con BEFORE: si el UPDATE falla después, la auditoría registró algo que nunca pasó.
2. No comparar `OLD` con `NEW`: se registran "cambios" que no ocurrieron. Usamos
   `NOT (OLD.x <=> NEW.x)`. `<=>` compara también con NULL (`NULL = NULL` da NULL, `NULL <=> NULL` da 1).

## Los 17 triggers

| Tabla | BEFORE (valida) | AFTER (registra) |
|---|---|---|
| envio | INSERT y UPDATE: peso mayor a 0, fecha no futura, **dirección del destinatario** | INSERT: estado RECIBIDO · UPDATE: **auditoría** y REDIRIGIDO si cambia el destino |
| historial_estado | INSERT: reglas de fechas · **UPDATE y DELETE: prohibidos** (inmutable) | — |
| viaje | INSERT y UPDATE: llegada, trasbordos, capacidad, **licencia, vehículo y chofer activos y libres ese día** · DELETE: no si tiene envíos | UPDATE y DELETE: **auditoría** |
| viaje_envio | INSERT: tramos, capacidad, viaje que no salió · DELETE: orden de tramos | — |
| incidente | INSERT y UPDATE: fecha dentro del viaje | — |
| usuario | — | UPDATE: **auditoría** y cierre de sesiones (baja o cambio de rol) |

En negrita, lo que agregó el script 013.

## Cómo lo aplicamos

- **Historial inmutable** en la base, no solo en la API. Para limpiar datos de prueba existe un
  modo mantenimiento (`SET @mantenimiento = 1` en esa conexión) que la app nunca usa. Contra una
  persona con acceso a la base protege otra cosa: los permisos (tema seguridad).
- **Auditoría** (el ejemplo de la cátedra): tabla `auditoria` + `procedimiento_auditar`, llamado
  por los triggers AFTER de envío, viaje y usuario. Guarda tabla, clave, campo, antes, después,
  **quién** y cuándo. El quién es `@usuario_app`: la app lo fija en cada pedido (lo hicimos en
  sesiones). Si el cambio viene de HeidiSQL, queda `CURRENT_USER()` (ej. root@localhost). La
  contraseña no se guarda: solo "cambiada". Pantalla **Auditoría** (admin) y botón "Ver cambios".
- **Reglas que antes solo controlaba el asistente** ahora están en la base. Por eso la pantalla
  Viajes también las respeta. Se controlan en el alta y cuando se tocan esas columnas: los datos
  de 2024 (604 viajes con la licencia equivocada) se pueden seguir editando en otros campos.
- **Un trigger llama a un procedimiento** (`procedimiento_validar_recursos_viaje`,
  `procedimiento_validar_direccion_envio`, `procedimiento_auditar`) para no repetir la regla en
  el INSERT y en el UPDATE.
- **Cada regla vive una sola vez, en la base** (cambio 020). El PHP valida solo el formato
  (obligatorio, número, largo) y maneja la transacción; las reglas del negocio (dirección del
  destinatario, licencia, vehículo/chofer libres, viaje que ya salió, capacidad, fechas, origen
  distinto del destino) están en triggers, procedimientos y CHECK, y su mensaje llega tal cual a
  la pantalla. Si una regla estuviera en los dos lados y se cambiara solo una copia, quedarían en
  desacuerdo. La lista de licencias para camión está en `funcion_licencia_maneja_especial`; la
  pantalla le pide a la base el dato "habilitado" de cada chofer para ofrecer solo esos (ayuda,
  no regla).
- **REDIRIGIDO** se registra donde está el paquete (sucursal de su último estado), no en el
  destino nuevo.

## Demo

1. HeidiSQL: `UPDATE historial_estado SET estado = 'ENTREGADO' WHERE id_historial = 1;` → error.
2. Viajes: crear un viaje con el camión AE882TB y el chofer Miranda, Sergio (legajo 1, licencia B) → mensaje de la base.
3. Envíos: editar el peso de un envío. Como admin, botón "Ver cambios" → la fila con el usuario.
4. Cerrar uno de los viajes en curso (los muestra `datos_de_hoy.php`) → la auditoría muestra la
   llegada real que puso el procedimiento.

## El modelo de la cátedra y el nuestro

En el ejemplo de la cátedra, `envios` tiene una columna `estado_id` y un trigger AFTER UPDATE
llena el historial. En LogiTrack el estado **no** se guarda en envío: el historial es la fuente
(se inserta una fila por cambio) y `funcion_ultimo_estado` da el estado actual. Así no hay dos
lugares que puedan contradecirse. La auditoría de la cátedra la tenemos igual, para los demás
campos del envío.
