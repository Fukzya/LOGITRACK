#!/usr/bin/env bash
# Mide las consultas que hace la app (las mismas que arman los modelos) sobre una base.
# Uso (Git Bash):  bash database/pruebas/medir_consultas.sh logitrack_carga
# Cada consulta se corre 3 veces y se informa el promedio en milisegundos (incluye abrir la
# conexión, unos 30 ms, igual que un pedido de la app).
BASE="${1:-logitrack_carga}"
MYSQL="/c/xampp/mysql/bin/mysql -uroot --default-character-set=utf8mb4 -N $BASE"

declare -a NOMBRES CONSULTAS
agregar() { NOMBRES+=("$1"); CONSULTAS+=("$2"); }

# Envíos e Historial: como Modelo::paginarPorClave (cuenta y elige las 50 claves en la tabla,
# después trae esas filas de la vista).
agregar "Envíos: primera página" \
 "SELECT COUNT(*) FROM envio v WHERE v.activo = 1;
  SELECT d.* FROM (SELECT nro_envio FROM envio v WHERE v.activo = 1 ORDER BY v.nro_envio DESC LIMIT 50) p
    JOIN vista_envios_detalle d USING (nro_envio);
  SELECT nro_envio, funcion_ultimo_estado(nro_envio) FROM envio ORDER BY nro_envio DESC LIMIT 50;"
agregar "Envíos: filtro por fecha" \
 "SELECT COUNT(*) FROM envio v WHERE v.activo = 1 AND v.fecha_envio >= '2026-11-01';
  SELECT d.* FROM (SELECT nro_envio FROM envio v WHERE v.activo = 1 AND v.fecha_envio >= '2026-11-01' ORDER BY v.nro_envio DESC LIMIT 50) p
    JOIN vista_envios_detalle d USING (nro_envio);"
agregar "Envíos: solo pendientes" \
 "SELECT COUNT(*) FROM envio v WHERE v.activo = 1 AND funcion_envio_terminado(v.nro_envio) = 0;
  SELECT nro_envio FROM envio v WHERE v.activo = 1 AND funcion_envio_terminado(v.nro_envio) = 0 ORDER BY v.nro_envio DESC LIMIT 50;"
agregar "Historial: primera página" \
 "SELECT COUNT(*) FROM historial_estado h;
  SELECT d.* FROM (SELECT id_historial FROM historial_estado h ORDER BY h.id_historial DESC LIMIT 50) p
    JOIN vista_historial_completo d USING (id_historial);"
agregar "Historial de un envío" \
 "SELECT h.* FROM vista_historial_completo h WHERE h.nro_envio = 8113 ORDER BY h.id_historial DESC LIMIT 50;"
agregar "Último estado (1 envío)" \
 "SELECT funcion_ultimo_estado(8113), funcion_envio_terminado(8113);"
agregar "Menú: tablero" \
 "SELECT COUNT(*), SUM(fecha_envio >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) FROM vista_envios_pendientes;
  SELECT COUNT(*), SUM(horas_atraso > 0) FROM vista_viajes_en_curso;
  SELECT COUNT(*) FROM vista_viajes_programados WHERE fecha_salida < NOW() + INTERVAL 7 DAY;
  SELECT cliente_dni, nombre_completo, total_envios FROM vista_clientes_con_envios WHERE activo = 1 ORDER BY total_envios DESC LIMIT 5;"
agregar "Viajes: primera página" \
 "SELECT COUNT(*) FROM viaje v;
  SELECT v.viaje_id, so.sucursal_completa, sd.sucursal_completa, CONCAT(c.nombre,' ',c.apellido),
         (SELECT COUNT(*) FROM viaje_envio x WHERE x.viaje_id = v.viaje_id), funcion_carga_viaje(v.viaje_id)
    FROM viaje v JOIN vehiculo ve ON ve.vehiculo_patente = v.vehiculo_patente
    JOIN tipo_vehiculo tv ON tv.tipo_veh_cod = ve.tipo_veh_cod JOIN chofer c ON c.chofer_legajo = v.chofer_legajo
    JOIN vista_sucursal_ubicacion so ON so.sucursal_cod = v.sucursal_origen_cod
    JOIN vista_sucursal_ubicacion sd ON sd.sucursal_cod = v.sucursal_destino_cod
   ORDER BY v.viaje_id DESC LIMIT 50;"
agregar "Viajes: solo en curso" \
 "SELECT COUNT(*) FROM viaje v WHERE v.viaje_id IN (SELECT viaje_id FROM vista_viajes_en_curso);"
agregar "Asistente: buscar rutas" \
 "CALL procedimiento_buscar_rutas('010', '012', 5);"
agregar "Resumen por sucursal (mes)" \
 "CALL procedimiento_resumen_por_sucursal('2026-11-01', NULL);"
agregar "Clientes: buscar 'Gómez'" \
 "SELECT COUNT(*) FROM cliente c WHERE c.activo = 1 AND (CAST(c.cliente_dni AS CHAR) LIKE 'Gómez%' OR CONCAT(c.nombre,' ',c.apellido) LIKE '%Gómez%' OR CONCAT(c.apellido,' ',c.nombre) LIKE '%Gómez%');
  SELECT c.* FROM cliente c WHERE c.activo = 1 AND (CAST(c.cliente_dni AS CHAR) LIKE 'Gómez%' OR CONCAT(c.nombre,' ',c.apellido) LIKE '%Gómez%' OR CONCAT(c.apellido,' ',c.nombre) LIKE '%Gómez%') ORDER BY c.apellido LIMIT 50;"
agregar "Alta de envío + asignar a viaje" \
 "START TRANSACTION;
  CALL procedimiento_crear_envio(CURDATE(), 5, 'STD', 10009006, 10002166, '010', '012', 6241);
  CALL procedimiento_asignar_envio_a_viaje((SELECT MIN(viaje_id) FROM vista_viajes_programados WHERE sucursal_origen_cod = '010'), LAST_INSERT_ID());
  ROLLBACK;"

printf "%-34s %8s\n" "Consulta ($BASE)" "ms"
for i in "${!NOMBRES[@]}"; do
    total=0
    for r in 1 2 3; do
        s=$(date +%s%N)
        $MYSQL -e "${CONSULTAS[$i]}" > /dev/null 2>/tmp/medir_err || { echo "ERROR en ${NOMBRES[$i]}: $(cat /tmp/medir_err)"; }
        e=$(date +%s%N)
        total=$(( total + (e - s) / 1000000 ))
    done
    printf "%-34s %8d\n" "${NOMBRES[$i]}" $(( total / 3 ))
done
