# Vistas: resumen para la defensa

## Qué es una vista

Una consulta guardada con nombre que se usa como si fuera una tabla. **No guarda datos**: cada
vez que se consulta, la base ejecuta la consulta de adentro. Sirve para:

| Para qué | Ejemplo nuestro |
|---|---|
| Ocultar JOINs | `vista_sucursal_ubicacion` une sucursal + localidad + provincia y arma "Sucursal Puerto 46 (Mendoza)". La usan 14 consultas. Si cambia el formato, se cambia en un solo lugar |
| Dejar una regla en un solo lugar | "Viaje en curso" = salió y no llegó. Lo define `vista_viajes_en_curso`, y la usan el filtro de Viajes y el menú |
| Reportes | `vista_clientes_con_envios`, `vista_envios_pendientes`: las tarjetas del menú principal |
| Seguridad | Mostrar solo algunas columnas y dar `GRANT SELECT` sobre la vista y no sobre la tabla (lo hacemos en el tema seguridad) |

## Las 9 vistas

| Vista | La usa | Actualizable |
|---|---|---|
| vista_sucursal_ubicacion | 14 consultas (sucursal con provincia) | Sí |
| vista_envios_detalle | Listado de Envíos | Sí |
| vista_historial_completo | Listado del Historial | Sí |
| vista_envios_pendientes | Menú: "Envíos pendientes" | Sí (solo las columnas de envío) |
| vista_envios_sin_viaje (021) | Menú: "N sin viaje asignado", filtro "Sin viaje" y botón "Asignar viaje" de Envíos | Sí (solo las columnas de envío) |
| vista_viajes_en_curso | Menú y filtro "Solo en curso" | Sí (solo las columnas de viaje) |
| vista_viajes_programados | Asistente (rutas) | **No**: `ALGORITHM = TEMPTABLE` |
| vista_clientes_con_envios | Menú: clientes con más envíos | Sí (las cuentas no) |
| vista_sesiones_abiertas | Pantalla Sesiones | Sí |

**Actualizable:** se puede hacer UPDATE sobre la vista y cambia la tabla de abajo, siempre que
cada fila de la vista sea una fila de una tabla. No lo es si usa GROUP BY, DISTINCT, agregados o
TEMPTABLE. La `vista_clientes_con_envios` original usaba GROUP BY y no era actualizable. La nueva
usa subconsultas: el mail sí se puede actualizar, las cuentas no.

**Una vista que usa otra:** las nuevas usan `vista_sucursal_ubicacion` para el nombre de la
sucursal con provincia.

## Qué se corrigió (script 014)

| Vista | Antes | Ahora |
|---|---|---|
| envios_pendientes | Último estado por **id**: un estado cargado con fecha pasada daba un resultado equivocado. Incluía 3 envíos dados de baja | Por fecha (igual que `funcion_ultimo_estado`). Solo los activos. Agrega dónde está y cuántos días lleva sin moverse |
| viajes_activos → viajes_en_curso | 515 viajes: 208 futuros + 284 de 2024 + 23 en curso | Solo los que salieron y no llegaron, con horas de atraso |
| clientes_con_envios | Dos LEFT JOIN a envío que se multiplicaban (7 × 7 = 49 filas) y se corregían con `COUNT(DISTINCT)`: 0,5 s | Subconsultas que usan su índice: mismos números, 0,1 s |
| envios_detalle, historial_completo | Sucursal sin provincia. Nadie las usaba | Las usan los listados: el modelo quedó en `FROM vista_envios_detalle` |

## Dos cosas que medimos (sirven también para índices)

1. **Una función dentro de una vista puede ser cara.** Con `funcion_ultimo_estado` en
   `vista_envios_detalle`, una página de 50 envíos tardaba 550 ms. `EXPLAIN` mostró
   `Using temporary; Using filesort`: la base ordena todas las filas y en el camino calcula la
   función para los 8.000 envíos. Sin la función en la vista (el modelo la calcula después solo
   para las 50 filas de la página): 110 ms.
2. **Cada JOIN de la vista se ejecuta siempre**, aunque nadie pida esa columna. El historial con
   remitente y destinatario sumaba 130 ms por página que nadie veía: se sacaron.

## Datos de prueba

Los datos de 2024 se generaron al azar y muchos quedaron a mitad de camino: las tarjetas
mostraban 4.879 envíos pendientes y 284 viajes "en curso" desde hacía dos años. El cambio 019
los cerró (quedan como historia) y los datos actuales los genera
`database/pruebas/datos_de_hoy.php` (de 3 días antes de hoy al domingo de la semana que viene):
las tarjetas muestran solo lo que pasa ahora.

## Envíos sin viaje (script 021)

**El problema:** el asistente deja crear un envío con "Asignar más tarde", pero después no había
dónde verlo. "Solo pendientes" muestra los **no entregados** (623), que no es lo mismo que **sin
viaje** (11).

**Qué es:** `vista_envios_sin_viaje` = `vista_envios_pendientes` + `NOT EXISTS` en `viaje_envio`.
Es una **vista sobre otra vista**: "pendiente" se define una vez y "sin viaje" se agrega arriba.

**Dónde se usa (la definición está en un solo lugar):**

| Lugar | Cómo la usa |
|---|---|
| Menú, tarjeta "Envíos pendientes" | `COUNT(*)` → "11 sin viaje asignado" (link a Envíos con el filtro) |
| Envíos, filtro "Sin viaje" | `nro_envio IN (SELECT nro_envio FROM vista_envios_sin_viaje)` |
| Envíos, botón "Asignar viaje" | Se muestra si la fila está en la vista (se calcula para las 50 de la página) |
| Asistente, modo asignar (`nuevo_envio.php?asignar=N`) | Empieza en el paso 3 (rutas). Al confirmar, en **una transacción**: `SELECT ... FOR UPDATE` del envío, lee la vista (¿sigue sin viaje?), crea el viaje si es nuevo y asigna con el procedimiento |

**Para la defensa:** bloquear primero y leer después (lo mismo que en transacciones): si dos
operadores asignan el mismo envío a la vez, el segundo espera el bloqueo, ve que ya tiene viaje y
recibe un 409. Probado con dos sesiones: queda una sola asignación.
