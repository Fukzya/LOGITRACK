#!/usr/bin/env bash
# Demo de concurrencia (tema transacciones): dos sesiones asignan envíos al MISMO viaje a la vez.
# Uso (Git Bash):  bash database/pruebas/demo_concurrencia.sh [base]
#
# Arma una moto de prueba (50 kg) con dos envíos de 30 kg y corre dos carreras:
#   Caso 1 - asignación directa (pantalla "Asignar envío a viaje"): A y B asignan a la vez.
#   Caso 2 - como el asistente: B ya leyó algo (tiene su "foto") y después bloquea el viaje.
# Antes del script 017 los dos casos terminan con 60 kg en la moto de 50 (actualización perdida).
# Con el 017 la segunda asignación espera a la primera y se rechaza por capacidad.
# Al final borra solo lo que creó (viaje, envíos, sus estados y su auditoría).
BASE="${1:-logitrack}"
CNF="${LOGITRACK_ROOT_CNF:-$HOME/.logitrack/root.cnf}"
MYSQL="/c/xampp/mysql/bin/mysql --defaults-extra-file=$CNF --default-character-set=utf8mb4 --unbuffered -N $BASE"
SALIDA_A="$(mktemp)"

# Un día con la moto BO180JH y un chofer libres (dentro de los próximos 60 días).
read -r DIA CHOFER < <($MYSQL -e "
  SELECT d.dia, (SELECT c.chofer_legajo FROM chofer c WHERE c.activo = 1 AND NOT EXISTS
                   (SELECT 1 FROM viaje v WHERE v.chofer_legajo = c.chofer_legajo AND DATE(v.fecha_salida) = d.dia)
                 ORDER BY c.chofer_legajo LIMIT 1)
    FROM (SELECT CURDATE() + INTERVAL 30 + n DAY AS dia
            FROM (SELECT 0 n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) x) d
   WHERE NOT EXISTS (SELECT 1 FROM viaje v WHERE v.vehiculo_patente = 'BO180JH' AND DATE(v.fecha_salida) = d.dia)
   ORDER BY d.dia LIMIT 1")
# Un destinatario con dirección activa y un remitente distinto.
read -r DEST DIR REM < <($MYSQL -e "
  SELECT d.cliente_dni, d.direccion_id, (SELECT c.cliente_dni FROM cliente c WHERE c.activo = 1 AND c.cliente_dni <> d.cliente_dni LIMIT 1)
    FROM direccion d JOIN cliente c ON c.cliente_dni = d.cliente_dni
   WHERE d.activo = 1 AND c.activo = 1 LIMIT 1")

VIAJE=$($MYSQL -e "INSERT INTO viaje (fecha_salida, sucursal_origen_cod, sucursal_destino_cod, vehiculo_patente, chofer_legajo)
                   VALUES ('$DIA 10:00', '010', '012', 'BO180JH', $CHOFER); SELECT LAST_INSERT_ID();")
E1=$($MYSQL -e "CALL procedimiento_crear_envio(CURDATE(), 30, 'STD', $REM, $DEST, '010', '012', $DIR)")
E2=$($MYSQL -e "CALL procedimiento_crear_envio(CURDATE(), 30, 'STD', $REM, $DEST, '010', '012', $DIR)")

limpiar() {
    $MYSQL -e "SET @mantenimiento = 1;
               DELETE FROM viaje_envio WHERE viaje_id = $VIAJE;
               DELETE FROM historial_estado WHERE nro_envio IN ($E1, $E2);
               DELETE FROM envio WHERE nro_envio IN ($E1, $E2);
               DELETE FROM viaje WHERE viaje_id = $VIAJE;
               DELETE FROM auditoria WHERE (tabla = 'envio' AND clave IN ('$E1', '$E2'))
                                        OR (tabla = 'viaje' AND clave = '$VIAJE');
               SET @mantenimiento = NULL;"
    rm -f "$SALIDA_A"
}
trap limpiar EXIT

echo "Viaje de prueba $VIAJE: moto BO180JH, capacidad 50 kg, sale el $DIA. Envíos $E1 y $E2 de 30 kg cada uno."

carrera() {   # $1 = título, $2 = lo que hace B antes de asignar
    $MYSQL -e "DELETE FROM viaje_envio WHERE viaje_id = $VIAJE"
    echo; echo "== $1"
    # A: asigna y tarda 3 s en confirmar (como si el operador A siguiera trabajando).
    # (El SQL va por la entrada estándar: así los acentos llegan bien en Windows.)
    echo "START TRANSACTION;
          CALL procedimiento_asignar_envio_a_viaje($VIAJE, $E1);
          SELECT CONCAT('A: asignó el envío $E1 a las ', TIME(NOW()), ' (sin COMMIT todavía)');
          SELECT SLEEP(3) INTO @espera;
          COMMIT; SELECT CONCAT('A: COMMIT a las ', TIME(NOW()));" | $MYSQL > "$SALIDA_A" 2>&1 &
    sleep 1
    # B: un segundo después asigna el otro envío al mismo viaje.
    echo "START TRANSACTION; $2
          SELECT CONCAT('B: asigna el envío $E2 a las ', TIME(NOW()));
          CALL procedimiento_asignar_envio_a_viaje($VIAJE, $E2);
          COMMIT; SELECT CONCAT('B: COMMIT a las ', TIME(NOW()));" | $MYSQL 2>&1 | sed 's/^ERROR.*: /B: RECHAZADO -> /'
    wait
    cat "$SALIDA_A" | grep '^A:'
    echo "RESULTADO: carga del viaje = $($MYSQL -e "SELECT funcion_carga_viaje($VIAJE)") kg (capacidad 50)"
}

carrera "Caso 1: asignación directa, los dos a la vez" ""
carrera "Caso 2: B ya tiene su foto (leyó antes) y bloquea el viaje con FOR UPDATE" \
        "SELECT COUNT(*) INTO @foto FROM envio WHERE nro_envio = $E2;
         SELECT CONCAT('B: carga que ve antes = ', funcion_carga_viaje($VIAJE));
         SELECT viaje_id INTO @v FROM viaje WHERE viaje_id = $VIAJE FOR UPDATE;
         SELECT CONCAT('B: consiguió el bloqueo a las ', TIME(NOW()), '; carga que ve en su foto = ', funcion_carga_viaje($VIAJE));"
