# Repaso de la base de datos: funciones, procedimientos, triggers, vistas y transacciones

Guía de estudio para la defensa. Todo sale de la base real (`information_schema`) y del código PHP.

En LogiTrack hay **7 funciones, 13 procedimientos, 17 triggers y 9 vistas**.

## Índice

0. [Las cuatro piezas en una línea](#0-las-cuatro-piezas-en-una-línea)
1. [Funciones](#1-funciones)
2. [Las funciones paso a paso: quién les pregunta](#2-las-funciones-paso-a-paso-quién-les-pregunta)
3. [Carga del viaje: con bloqueo y sin bloqueo](#3-carga-del-viaje-con-bloqueo-y-sin-bloqueo)
4. [¿Por qué esas funciones están en la base y no en el PHP?](#4-por-qué-esas-funciones-están-en-la-base-y-no-en-el-php)
5. [Procedimientos](#5-procedimientos)
6. [Triggers](#6-triggers-por-tabla)
7. [Vistas](#7-vistas)
8. [Transacciones](#8-transacciones)
9. [Lo que no cierra](#9-lo-que-no-cierra)
10. [Preguntas que te pueden hacer](#10-preguntas-que-te-pueden-hacer)

---

## 0. Las cuatro piezas en una línea

| Pieza | Qué es | Cómo se ejecuta |
|---|---|---|
| **Función** | Una "calculadora" que responde una pregunta y devuelve **un valor** | Alguien la usa dentro de un SELECT (el PHP, una vista, un trigger o un procedimiento) |
| **Procedimiento** | Un conjunto de acciones (validar, insertar, actualizar). Puede devolver datos por `OUT` o con un SELECT | Alguien lo llama con `CALL` |
| **Trigger** | Un control que la base **corre sola** | Se dispara solo ante un INSERT, UPDATE o DELETE en su tabla. Nadie lo llama |
| **Vista** | Un SELECT guardado con nombre | Se consulta como una tabla: `SELECT * FROM vista` |

---

## 1. Funciones

| Función | Qué calcula | Devuelve | Dónde se usa |
|---|---|---|---|
| `funcion_carga_viaje(viaje)` | Suma de los kg de los envíos del viaje | DECIMAL (kg) | Vistas de viajes en curso y programados; `Viaje.php` (listado, detalle, "Agregar envíos pendientes", para mostrar solo los que entran) |
| `funcion_carga_viaje_bloqueando(viaje)` | Lo mismo, pero con `LOCK IN SHARE MODE` | DECIMAL (kg) | Solo en triggers: asignar envío y cambiar vehículo. Evita que dos asignaciones a la vez superen la capacidad (cambio 017) |
| `funcion_ultimo_estado(envio)` | Último estado del historial (por fecha y después por id) | Código, p. ej. `EN_TRANSITO`, o NULL | La usa `funcion_envio_terminado`, el trigger de asignar y la columna "Último estado" (`Modelo::conNombreDeEstado`) |
| `funcion_envio_terminado(envio)` | Si ese último estado es final (Entregado o Devuelto) | 1 / 0 | `procedimiento_cerrar_viaje` (no toca los entregados), `procedimiento_resumen_por_sucursal` (pendientes), filtro "Solo pendientes" de Envíos |
| `funcion_licencia_maneja_especial(lic)` | Si la licencia es C o E | 1 / 0 | `procedimiento_validar_recursos_viaje` y `Viaje::recursosLibres` (el JS deshabilita los choferes que no pueden manejar camión) |
| `funcion_minutos_sesion()` | El tiempo de vencimiento de la sesión | 30 | `funcion_sesion_activa`, `procedimiento_cerrar_sesiones_vencidas`, `vista_sesiones_abiertas`, `config/session.php` |
| `funcion_sesion_activa(sesion)` | Si la sesión no está cerrada y se usó hace menos de 30 min | 1 / 0 | `Sesion::estado`, en cada pedido (`verificar_sesion`) |

**Por qué son funciones:** la misma regla, por ejemplo "¿está terminado?", se escribe una sola vez y la usan las vistas, los triggers y el PHP.

---

## 2. Las funciones paso a paso: quién les pregunta

### Una función sola no hace nada

`funcion_carga_viaje(3055)` responde **"¿Cuántos kg lleva el viaje 3055?"** → `40`.

La calculadora no se prende sola: **alguien tiene que preguntarle**. En LogiTrack le preguntan dos:

| Quién le pregunta | Para qué |
|---|---|
| **El PHP** (las pantallas) | Para **mostrar** el número |
| **Un trigger** (la base) | Para **decidir** si deja pasar algo |

### Cuando pregunta el PHP (para mostrar)

```
Abrís la pantalla "Viajes"
   ↓
El PHP pide: SELECT viaje_id, funcion_carga_viaje(viaje_id) ...
   ↓
La función responde 40
   ↓
En la pantalla ves:  Viaje 3055 | Carga: 40 kg
```

### Cuando pregunta un trigger (para decidir)

Asignás un envío de 15 kg al viaje 3055 (una moto de 50 kg):

```
Tocás "Asignar"
   ↓
El PHP hace: INSERT INTO viaje_envio (3055, envío de 15 kg)
   ↓
ANTES de guardar, la base corre sola el trigger_before_insert_viaje_envio
   ↓
El trigger le pregunta a la función: "¿cuánto lleva el 3055?"
   (funcion_carga_viaje_bloqueando(3055) → 40)
   ↓
El trigger hace la cuenta: 40 + 15 = 55 > 50
   ↓
Rechaza: "El viaje 3055 no tiene lugar: quedan 10 kg libres..."
```

| | Quién pregunta | Para qué | Qué función |
|---|---|---|---|
| Pantalla Viajes | PHP | Mostrar | `funcion_carga_viaje` |
| Asignar un envío | Trigger | Decidir | `funcion_carga_viaje_bloqueando` |

---

## 3. Carga del viaje: con bloqueo y sin bloqueo

**La regla:** *si con el número solo muestro, uso la normal. Si con el número decido si guardo algo, uso la que bloquea.*

| Si la carga es para… | Función | Por qué |
|---|---|---|
| **Mostrarla** (listados, tablero, detalle, vistas) | `funcion_carga_viaje` | Nadie escribe con ese número. Si cambia un segundo después, en la próxima carga de la pantalla se ve bien |
| **Decidir y después escribir** ("¿entra este envío? Si entra, lo asigno") | `funcion_carga_viaje_bloqueando` | Se lee el número real **y nadie lo puede cambiar hasta terminar de guardar**. Si otro quiere asignar al mismo viaje, espera |

### Ejemplo: la moto de 50 kg con 0 kg cargados

**Sin bloqueo** (lo que pasaba antes del 017):

| Tiempo | Operador A (envío de 30 kg) | Operador B (envío de 30 kg) |
|---|---|---|
| 1 | Lee la carga: 0 kg → "entra" | |
| 2 | | Lee la carga: 0 kg → "entra" |
| 3 | INSERT ✔ | |
| 4 | | INSERT ✔ |
| | **Resultado: 60 kg en una moto de 50 kg** ❌ | |

**Con bloqueo:**

| Tiempo | Operador A | Operador B |
|---|---|---|
| 1 | `FOR UPDATE` del viaje + lee con bloqueo: 0 kg → entra | |
| 2 | | Quiere bloquear el mismo viaje → **espera** ⏳ |
| 3 | INSERT y COMMIT → se libera | |
| 4 | | Ahora lee: **30 kg** → quedan 20 libres → **rechazado** ✔ |

### Dónde está cada una

| Lugar | Función | Motivo |
|---|---|---|
| `trigger_before_insert_viaje_envio` | **bloqueando** | Decide si el envío entra y enseguida se hace el INSERT |
| `trigger_before_update_viaje` (cambio de vehículo) | **bloqueando** | Decide si el vehículo nuevo soporta la carga y se hace el UPDATE |
| `vista_viajes_en_curso`, `vista_viajes_programados` | normal | Solo muestran |
| `Viaje.php` (listado, detalle, lista de "agregar envíos") | normal | Solo muestran o sugieren; la decisión final es del trigger |

**Aunque la lista diga que entra, la decisión final es del trigger.** "Agregar envíos pendientes" usa la función normal para *sugerir*. Si otra persona carga el viaje mientras tanto, el trigger vuelve a revisar con bloqueo y rechaza el envío.

### ¿Por qué no usar siempre la que bloquea?

1. **Frenaría a todos.** Abrir el listado pondría bloqueos sobre cientos de viajes. Mientras alguien mira la pantalla, otro que quiera asignar tendría que esperar.
2. **Fuera de una transacción no sirve de nada.** El bloqueo dura hasta el COMMIT. En un SELECT suelto, el COMMIT es inmediato y el bloqueo se libera en el acto.

### ¿Hay una transacción ahí?

Sí. **Cada INSERT, UPDATE o DELETE es una transacción**, aunque nadie escriba `START TRANSACTION`: si nadie la abre, la base la abre y la cierra sola (autocommit). El trigger **corre dentro de esa misma transacción**, así que el bloqueo dura hasta el COMMIT:

```
INSERT INTO viaje_envio ...        ← empieza la transacción
   ↓
   trigger: bloquea el viaje y lee la carga con bloqueo
   ↓
   ¿entra? → guarda la fila
   ↓
COMMIT                             ← termina y se liberan los bloqueos
```

| Desde dónde se asigna | Tipo de transacción | Qué entra en ella |
|---|---|---|
| Pantalla **Asignaciones** (un envío a un viaje) | **Automática** (autocommit) | Solo ese INSERT |
| **Asistente**, **"Agregar envíos pendientes"** y **viaje completo** | **Explícita**: el PHP hace `beginTransaction()` … `commit()` | Varias cosas juntas, p. ej. viaje nuevo + envío + 2 asignaciones |

`LOCK IN SHARE MODE` es un bloqueo **compartido**: muchos pueden leer a la vez, pero nadie puede modificar esas filas hasta el COMMIT. Lo que realmente hace esperar al operador B es el `FOR UPDATE` del viaje al principio del trigger. El bloqueo compartido agrega una segunda garantía: que nadie toque esos envíos mientras se hace la cuenta.

---

## 4. ¿Por qué esas funciones están en la base y no en el PHP?

### La idea clave: la base no puede llamar al PHP

El PHP puede preguntarle cosas a la base, pero **al revés no**. Un trigger o un procedimiento no pueden ejecutar una función de PHP.

Si **la base también necesita la respuesta**, la regla tiene que vivir en la base. Si estuviera en PHP, la base necesitaría su propia copia, y quedarían **dos copias de la misma regla**. Un día cambiás una y te olvidás de la otra.

### `funcion_envio_terminado`: "¿el envío ya terminó?"

| Quién la usa | Para qué |
|---|---|
| `procedimiento_cerrar_viaje` (base) | Al cerrar un viaje, **no** les pone "En sucursal" a los envíos ya entregados |
| `procedimiento_resumen_por_sucursal` (base) | Contar los pendientes de cada sucursal |
| PHP: filtro "Solo pendientes" | Mostrar solo los que no terminaron |

**Sin la función:** `cerrar_viaje` no puede preguntarle al PHP, así que tendría que repetir la regla ("busco el último estado y miro si es final"). La misma regla quedaría escrita 3 veces.

### `funcion_licencia_maneja_especial`: "¿esta licencia puede manejar camión?"

| Quién la usa | Para qué |
|---|---|
| `procedimiento_validar_recursos_viaje` (base, desde el trigger de viaje) | **Rechazar** un viaje en camión con un chofer sin licencia C o E |
| PHP: `Viaje::recursosLibres` | **Mostrar en gris** en la pantalla a los choferes que no pueden manejarlo |

**Por qué importa:** antes del cambio 020, el PHP tenía su propia lista `['C','E']` y la base tenía otra. Si mañana se agrega la licencia D, con dos listas podía pasar que la pantalla te deje elegir al chofer y la base lo rechace, o al revés. Ahora se cambia en un solo lugar y **la pantalla y el control nunca se contradicen**.

### `funcion_minutos_sesion`: "¿cuántos minutos dura una sesión?"

| Quién la usa | Para qué |
|---|---|
| `funcion_sesion_activa` (base) | Decidir si la sesión sigue viva |
| `procedimiento_cerrar_sesiones_vencidas` (base) | Cerrar las que pasaron el tiempo |
| `vista_sesiones_abiertas` (base) | Listar solo las vivas |
| PHP: `config/session.php` | Saber el tiempo de vencimiento |

**Por qué una función que solo devuelve 30:** MariaDB **no tiene constantes globales**. Sin la función, el número 30 estaría escrito 4 veces. Pasarlo a 60 sería buscar y cambiar 4 lugares; con la función es uno solo.

Es la más floja de las tres; si la cuestionan: *"es una constante; la hicimos función porque MariaDB no tiene constantes y la usan cuatro objetos"*. Una alternativa sería una tabla de configuración, pero para un solo valor era más complicado.

### Resumen

| Pregunta | Respuesta |
|---|---|
| ¿Por qué en la base y no en PHP? | Porque **triggers, procedimientos y vistas** necesitan esa respuesta, y la base no puede llamar al PHP |
| ¿Y por qué el PHP también la usa? | Para **preguntarle a la base** en lugar de tener una copia propia: una regla, un solo lugar (cambio 020) |
| ¿Qué ganás? | Si la regla cambia, se cambia una vez y **la pantalla y los controles siempre coinciden** |

---

## 5. Procedimientos

### a) Los que llama la app

| Procedimiento | Qué hace | Se ejecuta cuando… | Código |
|---|---|---|---|
| `procedimiento_crear_envio(8 IN)` | INSERT del envío y devuelve el n.º con un SELECT | Alta en Envíos o en el asistente | `Envio::crear` ← `api/envios.php`, `api/asistente_envio.php` |
| `procedimiento_asignar_envio_a_viaje(viaje, envio)` | Revisa que no esté repetido y hace el INSERT en `viaje_envio` (las reglas las pone el trigger) | Asignaciones, "Agregar envíos" del detalle, viaje completo, asistente | `ViajeEnvio::asignarConProcedimiento` |
| `procedimiento_registrar_estado_envio(… OUT id)` | Valida que existan el envío, la sucursal y el estado; inserta el estado; devuelve el id | Botón "Cambiar estado" (Envíos) y alta en Historial | `HistorialEstado::crear` |
| `procedimiento_cerrar_viaje(viaje, llegada, OUT n)` | Ver el detalle abajo | Botón "Cerrar viaje" | `Viaje::cerrar` ← `api/viajes.php` (accion=cerrar) |
| `procedimiento_buscar_rutas(origen, destino, peso)` | Busca viajes directos y con un trasbordo que tengan lugar, ordenados por prioridad y llegada | Paso 3 del asistente | `Viaje::buscarRutas` |
| `procedimiento_resumen_por_sucursal(desde, hasta)` | Por sucursal: despachados, kg, con destino, entregados, viajes, pendientes | Pantalla "Resumen por sucursal" | `Sucursal::resumen` |
| `procedimiento_iniciar_sesion(… OUT id)` | Revisa que el usuario esté activo, cierra las sesiones vencidas e inserta la sesión | Login | `Sesion::iniciar` |
| `procedimiento_cerrar_sesion(id, motivo)` | Pone `fin` y el motivo; si ya estaba cerrada, da error | Logout y "cerrar" en la pantalla Sesiones | `Sesion::cerrar` |
| `procedimiento_cerrar_sesiones_vencidas()` | Cierra con motivo VENCIDA las que pasaron los 30 min | Dentro de `iniciar_sesion` y en la pantalla Sesiones | `Sesion::cerrarVencidas` |

**`procedimiento_cerrar_viaje` es el procedimiento modelo para la defensa:**

1. Abre `START TRANSACTION`.
2. Bloquea el viaje con `FOR UPDATE`.
3. Valida tres cosas: que exista, que no esté cerrado y que ya haya salido.
4. Carga la llegada.
5. Inserta `EN_SUCURSAL` para cada envío no terminado. Si la sucursal es su destino, la observación dice "llegó a destino"; si no, dice "trasbordo".
6. Hace `COMMIT`.

Si algo falla, el `EXIT HANDLER` hace `ROLLBACK` y con `RESIGNAL` el error llega a la app.

### b) Auxiliares: solo los llaman los triggers

| Procedimiento | Qué revisa o hace | Lo llama |
|---|---|---|
| `procedimiento_auditar(tabla, clave, op, campo, antes, después)` | Inserta en `auditoria` **solo si el valor cambió**. El usuario sale de `@usuario_app`; si no está, de `CURRENT_USER()` | Triggers AFTER de envío, viaje y usuario |
| `procedimiento_validar_direccion_envio(dir, destinatario)` | Que la dirección sea del destinatario y esté activa | Triggers de envío (alta y cambio) |
| `procedimiento_validar_fecha_incidente(viaje, fecha)` | Que la fecha no sea futura y esté entre la salida y la llegada del viaje | Triggers de incidente |
| `procedimiento_validar_recursos_viaje(viaje, salida, patente, legajo)` | Vehículo y chofer activos, licencia C/E si el vehículo la pide, que ninguno tenga otro viaje ese día | Triggers de viaje (alta y cambio) |

Están separados en procedimientos porque **el alta y la modificación usan la misma regla**: se escribe una vez y la llaman dos triggers.

---

## 6. Triggers (por tabla)

| Tabla | Trigger | Cuándo | Qué controla o hace |
|---|---|---|---|
| **envio** | before_insert | Antes del alta | Peso > 0, fecha no futura, dirección del destinatario |
| | after_insert | Después del alta | **Crea solo el primer estado `RECIBIDO`** en la sucursal de origen |
| | before_update | Antes de editar | Si cambia la fecha: no futura y no posterior a su primer estado. Peso > 0. Si cambia la dirección o el destinatario, la vuelve a validar |
| | after_update | Después de editar | Audita 9 columnas. **Si cambia el destino, agrega el estado `REDIRIGIDO`** en la sucursal donde está el envío |
| **historial_estado** | before_insert | Antes de agregar un estado | No futuro, no antes de la fecha del envío, no antes del último estado, nada después de uno final |
| | before_update / before_delete | Siempre | **Lo rechazan**: el historial es inmutable. Solo se puede con `@mantenimiento = 1` |
| **incidente** | before_insert / before_update | Alta o cambio de fecha o viaje | Fecha dentro del viaje (llama a `procedimiento_validar_fecha_incidente`) |
| **usuario** | after_update | Después de editar | Audita usuario, rol, activo y contraseña (sin guardar el valor). **Si se da de baja o cambia el rol, cierra sus sesiones** |
| **viaje** | before_insert | Antes del alta | Llegada > salida, llegada no futura, recursos (`procedimiento_validar_recursos_viaje`) |
| | before_update | Antes de editar | Lo mismo, más: **no romper un trasbordo** (2 controles) y que el **vehículo nuevo tenga capacidad** para la carga actual (con bloqueo) |
| | before_delete | Antes de borrar | No se borra si tiene envíos asignados |
| | after_update / after_delete | Después | Auditoría: las 6 columnas, o un resumen del viaje borrado |
| **viaje_envio** | before_insert | Antes de asignar | **El más completo:** bloquea el viaje (`FOR UPDATE`); el viaje no puede estar cerrado ni haber salido; el envío tiene que estar activo y no terminado; el viaje no puede salir antes de la fecha del envío; **calcula el tramo** (máx. 2); el trasbordo sale de donde llegó el primer viaje y después de su llegada; **capacidad** (kg libres) |
| | before_delete | Antes de quitar | No se puede quitar el tramo 1 si existe el tramo 2 |

**Para la defensa:**

- **BEFORE** valida y puede frenar la operación con `SIGNAL 45000`; el mensaje le llega tal cual al usuario.
- **AFTER** registra cosas que dependen de que la operación ya se hizo: historial y auditoría.
- **NEW** es la fila nueva y **OLD** la anterior.

---

## 7. Vistas

| Vista | Tablas que cruza | Qué muestra | Dónde se usa |
|---|---|---|---|
| `vista_sucursal_ubicacion` | sucursal + localidad + provincia | "Sucursal Puerto 46 (Mendoza)" y la provincia | **La base de casi todas las demás**; selects de catálogos; listados de viajes y asignaciones; `buscar_rutas` y `resumen` |
| `vista_envios_detalle` | envio + tipo_contenido + cliente ×2 + sucursal_ubicacion ×2 + direccion + localidad | El envío con nombres en lugar de códigos | Listado de Envíos (`Envio::listar`) |
| `vista_historial_completo` | historial + estado_envio + sucursal_ubicacion | Cada estado con su nombre, la sucursal y si es final | Listado de Historial |
| `vista_envios_pendientes` | envio + **último** historial (subconsulta) + estado_envio + cliente ×2 + sucursal_ubicacion ×3 | Envíos activos no terminados: dónde están y **días sin moverse** | Tarjeta "Pendientes" del menú; base de la vista siguiente |
| `vista_envios_sin_viaje` | vista_envios_pendientes + `NOT EXISTS` en viaje_envio | Pendientes sin ningún viaje | Pie de la tarjeta, filtro "Sin viaje", botón "Asignar viaje" del asistente |
| `vista_viajes_en_curso` | viaje + vehiculo + tipo_vehiculo + chofer + sucursal_ubicacion ×2 + `funcion_carga_viaje` | Viajes que salieron y no llegaron: carga, cantidad de envíos, **horas de atraso** | Tarjeta del menú, filtro "Solo en curso" |
| `vista_viajes_programados` | Las mismas tablas (ALGORITHM=TEMPTABLE) | Viajes futuros con capacidad, carga, **kg libres** y provincias | `buscar_rutas`, la lista de viajes del asistente, tarjeta del menú |
| `vista_clientes_con_envios` | cliente + localidad + provincia + conteos | Envíos de cada cliente como remitente y como destinatario | Tarjeta del menú |
| `vista_sesiones_abiertas` | sesion + usuario | Sesiones vivas, minutos conectado y sin uso | Pantalla Sesiones (admin) |

**Para qué sirven:** esconden los JOIN (el PHP hace `SELECT * FROM vista`) y la regla, por ejemplo qué cuenta como "pendiente", vive en un solo lugar. Las vistas se arman sobre otras: `sucursal_ubicacion` → `envios_pendientes` → `envios_sin_viaje`.

---

## 8. Transacciones

Hay **6 transacciones explícitas**: 5 las abre el PHP y 1 la abre la base.

### Las que abre el PHP (`beginTransaction` … `commit` / `rollBack`)

| # | Dónde (pantalla) | Archivo | Qué guarda junto | Si algo falla… |
|---|---|---|---|---|
| 1 | **Asistente "Nuevo envío"** | `api/asistente_envio.php` | Viaje nuevo (si lo pidió) + envío + 1 o 2 asignaciones (con trasbordo) | No queda nada: ni el viaje vacío, ni el envío sin viaje, ni el primer tramo sin el segundo |
| 2 | **Detalle de viaje → "Agregar envíos pendientes"** | `api/viajes.php` | Varias asignaciones al mismo viaje | Si uno no entra, no se agrega ninguno |
| 3 | **Viaje completo** (viaje + sus envíos de una vez) | `api/viaje_completo.php` | Viaje nuevo + sus asignaciones | No queda el viaje creado con la mitad de los envíos |
| 4 | **Alta de cliente** (desde el selector de clientes) | `api/clientes.php` | Cliente + su dirección | No queda un cliente sin dirección |
| 5 | **Alta de envío** (pantalla Envíos) | `modelos/Envio.php` | Envío + su estado RECIBIDO (lo crea el trigger) | No queda un envío sin historial |

### La que abre la base (`START TRANSACTION` dentro del procedimiento)

| # | Dónde | Objeto | Qué guarda junto | Si algo falla… |
|---|---|---|---|---|
| 6 | **Botón "Cerrar viaje"** | `procedimiento_cerrar_viaje` | Llegada del viaje + estado `EN_SUCURSAL` para cada envío | El `EXIT HANDLER` hace ROLLBACK: el viaje no queda cerrado con la mitad de los envíos actualizados |

### Cuáles defender primero

| Prioridad | Cuál | Por qué |
|---|---|---|
| ⭐ 1 | **Cerrar viaje (6)** | Es la transacción "de libro" dentro de la base: START TRANSACTION, FOR UPDATE, EXIT HANDLER con ROLLBACK y RESIGNAL, parámetro OUT |
| ⭐ 2 | **Asistente (1)** | La más grande. Muestra la transacción en el PHP con varios pasos y el bloqueo de concurrencia (la moto de 50 kg) |
| 3 | **Cliente + dirección (4)** | El ejemplo más fácil de explicar: dos INSERT que van juntos |

### Un punto débil, para que no te sorprenda

La **5 (alta de envío)** es la más floja. Un INSERT solo **ya es atómico**, y el trigger que crea el RECIBIDO corre dentro de ese mismo INSERT. Aunque no hubiera `beginTransaction`, si el trigger falla, el envío tampoco se guarda. La transacción explícita no hace daño, pero no agrega nada.

Si te preguntan, decilo así:

> "Un INSERT con su trigger ya es atómico. La transacción está para que el alta se pueda sumar a una más grande, como la del asistente."

Eso es lo que hace el código: si ya hay una transacción abierta, `Envio::crear` se suma a esa.

**Frase general para la defensa:**

> "El trigger corre dentro de la transacción del INSERT, así que el bloqueo dura hasta el COMMIT. Si la asignación es parte de algo más grande, como el asistente, el PHP abre una transacción explícita para que todo se guarde junto o no se guarde nada."

---

## 9. Lo que no cierra

Revisado en el código de los triggers (27/11/2026). Los puntos 1 y 2 son errores reales; están pendientes de decisión.

| # | Problema | Ejemplo |
|---|---|---|
| 1 | **Hay dos caminos para cerrar un viaje.** El formulario de Viajes deja cargar `fecha_llegada` a mano, y eso no pasa por `procedimiento_cerrar_viaje` | Edito el viaje y pongo la llegada: queda cerrado, pero sus envíos **no** reciben `EN_SUCURSAL` |
| 2 | **Cambiar el peso de un envío ya asignado no revisa la capacidad.** `trigger_before_update_envio` solo controla que sea > 0 | Un envío de 10 kg en una moto de 50 kg pasa a 500 kg y la base lo acepta |
| 3 | La regla "peso > 0" está dos veces en la base: el CHECK `ck_peso_positivo` y los triggers | No rompe nada, pero no cumple "una regla, un lugar" |
| 4 | `procedimiento_crear_envio` y `procedimiento_asignar_envio_a_viaje` siguen el estilo viejo: devuelven un SELECT en lugar de `OUT`, y el control de duplicado repite la PK `(viaje_id, nro_envio)` | Funcionan bien; solo no son consistentes con `procedimiento_registrar_estado_envio` |
| 5 | La auditoría registra cambios y borrados, **no altas** | Si preguntan "¿quién creó este envío?", la tabla auditoria no lo dice |

---

## 10. Preguntas que te pueden hacer

| Pregunta | Respuesta corta |
|---|---|
| ¿Función o procedimiento? | La función devuelve **un valor** y va dentro de un SELECT. El procedimiento se llama con `CALL` y puede tener transacción, varios resultados y `OUT` |
| ¿Por qué validar en un trigger y no en PHP? | Porque se cumple venga de donde venga el dato: la app, phpMyAdmin o un script (cambio 020) |
| ¿Por qué hay funciones en la base y no en PHP? | Porque triggers, procedimientos y vistas necesitan la respuesta, y la base no puede llamar al PHP |
| ¿Un trigger puede modificar la misma tabla? | No; por eso el `REDIRIGIDO` se inserta en **historial**, no en envio |
| ¿Por qué `FOR UPDATE` en el trigger de asignar? | Sin el bloqueo, dos asignaciones simultáneas leen la misma carga y juntas superan la capacidad (lo probamos con la moto: 60 kg en 50 kg) |
| ¿Por qué hay dos funciones de carga? | Una para mostrar (sin bloqueo, no frena a nadie) y otra para decidir antes de guardar (con bloqueo, dentro de la transacción) |
| ¿Un INSERT suelto es una transacción? | Sí (autocommit). El trigger corre dentro de ella; si el trigger falla, el INSERT tampoco se guarda |
| ¿Una vista guarda datos? | No; ejecuta su SELECT cada vez. TEMPTABLE hace que lo calcule una vez por consulta (1,6 s → 0,13 s) |
| ¿Por qué el procedimiento tiene `EXIT HANDLER`? | Para que, si algo falla en el medio, se haga `ROLLBACK` de todo y no quede el viaje cerrado con la mitad de los envíos actualizados |
