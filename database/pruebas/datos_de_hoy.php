<?php
/*
 * QUÉ ES: genera datos de prueba "actuales" para usar la aplicación y para la defensa:
 *         viajes, envíos, estados e incidentes desde 3 días antes de hoy hasta el domingo de la
 *         semana que viene (inclusive). Las fechas se calculan desde el día en que se corre.
 *
 * CÓMO SE USA (consola, en la carpeta del proyecto):
 *         C:\xampp\php\php database\pruebas\datos_de_hoy.php
 *         Pide la contraseña de root (o variable de entorno LOGITRACK_ROOT_CLAVE).
 *         Se puede correr las veces que haga falta, por ejemplo la noche antes de la defensa:
 *         cada vez BORRA los datos de 2025 en adelante y los vuelve a crear al día.
 *
 * OJO: borra TODO envío y viaje con fecha de 2025 en adelante, incluidos los que se hayan cargado
 *      a mano desde la aplicación. Los datos históricos (2024), los clientes, las sucursales, los
 *      vehículos y los choferes no se tocan.
 *
 * CÓMO RESPETA LAS REGLAS DE LA BASE: todo entra por los mismos caminos que la aplicación
 * (procedimientos de crear envío, asignar, registrar estado y cerrar viaje, con todos los
 * triggers activos). Hay una sola cosa que la base no deja hacer, a propósito: cargar el pasado.
 * El estado "Recibido" se graba con la hora actual y no se puede asignar un envío a un viaje que
 * ya salió. Para simular los días anteriores a hoy:
 *   1. Los viajes se crean con la salida corrida 500 días hacia adelante (fecha provisoria), se les
 *      asignan los envíos (pasando por capacidad, trasbordos, etc.) y después se les pone la
 *      salida real.
 *   2. La hora del "Recibido" automático se corrige en modo mantenimiento (@mantenimiento = 1).
 * Todo lo demás (salidas, llegadas, entregas) se registra en orden y con las reglas de siempre.
 * Al final se borra la auditoría que generan estas correcciones.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo por consola.'); }
mt_srand();

// CAMBIAR: cuántos días antes de hoy arranca la simulación.
const DIAS_ANTES = 3;
// CAMBIAR: viajes por día desde/hacia cada centro de distribución (además del troncal entre centros).
const VIAJES_POR_CENTRO = 8;
// CAMBIAR: los dos centros de distribución (todas las rutas pasan por uno de ellos).
const CENTROS = ['010', '012'];
// CAMBIAR: envíos que quedan sin viaje (para probar "asignar más tarde").
const ENVIOS_SIN_VIAJE = 10;
const DESFASE_DIAS = 500;   // fecha provisoria de los viajes mientras se asignan (ver arriba)

// ------------------------------------------------------------------ conexión (como root)
$claveRoot = getenv('LOGITRACK_ROOT_CLAVE');
if ($claveRoot === false) {
    echo 'Contraseña de root de MariaDB (Enter si no tiene): ';
    $claveRoot = trim((string)fgets(STDIN));
}
$pdo = new PDO('mysql:host=localhost;dbname=logitrack;charset=utf8mb4', 'root', $claveRoot,
               [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$pdo->exec("SET @usuario_app = 'carga_de_datos'");

function valor(PDO $pdo, string $sql, array $p = []) { $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchColumn(); }
function filas(PDO $pdo, string $sql, array $p = []): array { $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchAll(); }
function ejecutar(PDO $pdo, string $sql, array $p = []): void { $st = $pdo->prepare($sql); $st->execute($p); $st->closeCursor(); }
function azar(array $lista) { return $lista[array_rand($lista)]; }
function sumar(string $fechaHora, int $minutos): string { return date('Y-m-d H:i:s', strtotime($fechaHora) + $minutos * 60); }

$ahora   = (string)valor($pdo, 'SELECT NOW()');
$hoy     = substr($ahora, 0, 10);
$inicio  = date('Y-m-d', strtotime("$hoy -" . DIAS_ANTES . ' days'));
$diaSem  = (int)date('N', strtotime($hoy));                       // 1 = lunes ... 7 = domingo
$fin     = date('Y-m-d', strtotime("$hoy +" . (7 - $diaSem + 7) . ' days'));   // domingo de la semana que viene
$inicioCorrida = $ahora;
echo "Generando datos del $inicio al $fin (hoy: $ahora).\n";

// ------------------------------------------------------------------ 1) borrar lo generado antes
$envios = array_column(filas($pdo, "SELECT nro_envio FROM envio WHERE fecha_envio >= '2025-01-01'"), 'nro_envio');
$viajes = array_column(filas($pdo, "SELECT viaje_id FROM viaje WHERE fecha_salida >= '2025-01-01'"), 'viaje_id');
$pdo->exec('SET @mantenimiento = 1');   // el historial es inmutable: solo así se puede borrar
if ($envios || $viajes) {
    $e = $envios ? implode(',', $envios) : '0';
    $v = $viajes ? implode(',', $viajes) : '0';
    $pdo->exec("DELETE FROM viaje_envio WHERE tramo = 2 AND (nro_envio IN ($e) OR viaje_id IN ($v))");   // trasbordos primero
    $pdo->exec("DELETE FROM viaje_envio WHERE nro_envio IN ($e) OR viaje_id IN ($v)");
    $pdo->exec("DELETE FROM incidente WHERE viaje_id IN ($v)");
    $pdo->exec("DELETE FROM historial_estado WHERE nro_envio IN ($e)");
    $pdo->exec("DELETE FROM envio WHERE nro_envio IN ($e)");
    $pdo->exec("DELETE FROM viaje WHERE viaje_id IN ($v)");
    $pdo->exec("DELETE FROM auditoria WHERE (tabla = 'envio' AND clave IN ($e)) OR (tabla = 'viaje' AND clave IN ($v))");
}
// Los números siguen desde el último que quedó (sin huecos): con AUTO_INCREMENT = 1, MariaDB lo
// ajusta solo al máximo + 1.
foreach (['envio', 'viaje', 'historial_estado', 'incidente', 'auditoria'] as $tabla) {
    $pdo->exec("ALTER TABLE $tabla AUTO_INCREMENT = 1");
}
echo '  Borrados: ' . count($envios) . ' envíos y ' . count($viajes) . " viajes de 2025 en adelante.\n";

// ------------------------------------------------------------------ 2) recursos disponibles
$sucursales = array_column(filas($pdo, 'SELECT sucursal_cod FROM sucursal WHERE activo = 1'), 'sucursal_cod');
$otras      = array_values(array_diff($sucursales, CENTROS));
$vehiculos  = filas($pdo, 'SELECT v.vehiculo_patente AS patente, t.tipo_veh_nombre AS tipo, t.tipo_veh_capacidad_kg AS capacidad,
                                  t.tipo_veh_requiere_lic_especial AS especial
                             FROM vehiculo v JOIN tipo_vehiculo t ON t.tipo_veh_cod = v.tipo_veh_cod WHERE v.activo = 1');
$choferes   = filas($pdo, 'SELECT chofer_legajo AS legajo, chofer_tipo_licencia AS licencia FROM chofer WHERE activo = 1');
$destinos   = filas($pdo, 'SELECT d.cliente_dni AS dni, d.direccion_id AS dir FROM direccion d
                             JOIN cliente c ON c.cliente_dni = d.cliente_dni
                            WHERE d.activo = 1 AND c.activo = 1 ORDER BY RAND() LIMIT 3000');
$remitentes = array_column(filas($pdo, 'SELECT cliente_dni FROM cliente WHERE activo = 1 ORDER BY RAND() LIMIT 3000'), 'cliente_dni');

// Peso de un paquete según el vehículo (CAMBIAR: rangos de peso en kg).
function peso_para(array $vehiculo): float {
    return match (true) {
        $vehiculo['capacidad'] <= 50   => mt_rand(20, 500) / 100,      // moto: sobres y cajas chicas (0,2 a 5 kg)
        $vehiculo['capacidad'] <= 3000 => mt_rand(100, 30000) / 100,   // furgoneta: 1 a 300 kg
        default                        => mt_rand(2000, 50000) / 100,  // camión / semi: 20 a 500 kg
    };
}
function tipo_contenido(): string { $r = mt_rand(1, 100); return $r <= 80 ? 'STD' : ($r <= 95 ? 'FRG' : 'PEL'); }

// ------------------------------------------------------------------ 3) planificar los viajes
// Un vehículo y un chofer hacen como mucho un viaje por día; camión y semi con licencia C o E.
$plan = [];
for ($dia = $inicio; $dia <= $fin; $dia = date('Y-m-d', strtotime("$dia +1 day"))) {
    $usadosV = []; $usadosC = [];
    $rutas = [[CENTROS[0], CENTROS[1], '07:00'], [CENTROS[1], CENTROS[0], '08:00']];   // troncal entre centros
    foreach (CENTROS as $centro) {
        $elegidas = $otras;
        shuffle($elegidas);   // (los códigos son texto: '001', '100'... no se usan como claves de array)
        foreach (array_slice($elegidas, 0, VIAJES_POR_CENTRO) as $i => $suc) {
            $hora = sprintf('%02d:%02d', mt_rand(6, 19), azar([0, 15, 30, 45]));
            $rutas[] = $i % 2 ? [$suc, $centro, $hora] : [$centro, $suc, $hora];
        }
    }
    foreach ($rutas as $n => [$origen, $destino, $hora]) {
        // Troncal en camión o semi; el resto: furgoneta 50 %, camión 25 %, moto 15 %, semi 10 %.
        $r = mt_rand(1, 100);
        $tipo = $n < 2 ? azar(['Camión', 'Semi-remolque'])
              : ($r <= 50 ? 'Furgoneta' : ($r <= 75 ? 'Camión' : ($r <= 90 ? 'Moto mensajería' : 'Semi-remolque')));
        $candidatos = array_values(array_filter($vehiculos, fn($v) => $v['tipo'] === $tipo && !isset($usadosV[$v['patente']])));
        $vehiculo   = azar($candidatos);
        $licencias  = $vehiculo['especial'] ? ['C', 'E'] : ['B', 'C', 'D', 'E'];
        $chofer     = azar(array_values(array_filter($choferes,
                          fn($c) => in_array($c['licencia'], $licencias, true) && !isset($usadosC[$c['legajo']]))));
        $usadosV[$vehiculo['patente']] = true; $usadosC[$chofer['legajo']] = true;
        $plan[] = ['origen' => $origen, 'destino' => $destino, 'salida' => "$dia $hora:00",
                   'vehiculo' => $vehiculo, 'chofer' => $chofer['legajo'], 'carga' => 0.0, 'envios' => []];
    }
}
usort($plan, fn($a, $b) => strcmp($a['salida'], $b['salida']));

// Se crean con la salida provisoria (500 días después) para poder asignarles envíos.
foreach ($plan as &$v) {
    ejecutar($pdo, 'INSERT INTO viaje (fecha_salida, sucursal_origen_cod, sucursal_destino_cod, vehiculo_patente, chofer_legajo)
                    VALUES (:s, :o, :d, :p, :c)',
             [':s' => sumar($v['salida'], DESFASE_DIAS * 1440), ':o' => $v['origen'], ':d' => $v['destino'],
              ':p' => $v['vehiculo']['patente'], ':c' => $v['chofer']]);
    $v['id'] = (int)$pdo->lastInsertId();
}
unset($v);
echo '  Viajes: ' . count($plan) . ".\n";

// ------------------------------------------------------------------ 4) envíos y asignaciones
$datosEnvio = [];   // nro => ['fecha' => ..., 'tramos' => [indice de viaje...], 'destino' => ...]

function crear_envio(PDO $pdo, array $destinos, array $remitentes, string $fecha, float $peso, string $origen, string $destino): int {
    $d = azar($destinos);
    do { $rem = azar($remitentes); } while ($rem == $d['dni']);
    $st = $pdo->prepare('CALL procedimiento_crear_envio(:f, :p, :t, :r, :d, :o, :s, :dir)');
    $st->execute([':f' => $fecha, ':p' => $peso, ':t' => tipo_contenido(), ':r' => $rem, ':d' => $d['dni'],
                  ':o' => $origen, ':s' => $destino, ':dir' => $d['dir']]);
    $nro = (int)$st->fetchColumn();
    $st->closeCursor();
    return $nro;
}
function asignar(PDO $pdo, int $viaje, int $nro): void {
    ejecutar($pdo, 'CALL procedimiento_asignar_envio_a_viaje(:v, :e)', [':v' => $viaje, ':e' => $nro]);
}
// Fecha del envío: el día del viaje o el anterior, nunca futura (regla de la base).
function fecha_envio(string $salida, string $hoy): string {
    $f = date('Y-m-d', strtotime(substr($salida, 0, 10) . ' -' . mt_rand(0, 1) . ' days'));
    return min($f, $hoy);
}

foreach ($plan as $i => &$v) {
    $cap = (float)$v['vehiculo']['capacidad'];
    $cantidad = $cap <= 50 ? mt_rand(2, 4) : ($cap <= 3000 ? mt_rand(3, 8) : mt_rand(5, 12));
    // Los viajes de más de 2 días adelante todavía tienen poca carga: se van llenando con los días
    // (y queda lugar para probar el asistente). CAMBIAR: los días y la cantidad.
    $diasAdelante = (strtotime(substr($v['salida'], 0, 10)) - strtotime($hoy)) / 86400;
    if ($diasAdelante > 2) $cantidad = mt_rand(0, 2);
    for ($k = 0; $k < $cantidad; $k++) {
        $peso = peso_para($v['vehiculo']);
        if ($v['carga'] + $peso > $cap) break;
        $nro = crear_envio($pdo, $destinos, $remitentes, fecha_envio($v['salida'], $hoy), $peso, $v['origen'], $v['destino']);
        asignar($pdo, $v['id'], $nro);
        $v['carga'] += $peso; $v['envios'][] = $nro;
        $datosEnvio[$nro] = ['tramos' => [$i]];
    }
}
unset($v);

// Trasbordos: sucursal -> centro (viaje 1) y centro -> otra sucursal (viaje 2), que sale al menos
// 24 h después (la llegada estimada del primero) y como mucho 3 días después.
$trasbordos = 0;
foreach ($plan as $i => $v1) {
    if (!in_array($v1['destino'], CENTROS, true) || in_array($v1['origen'], CENTROS, true) || mt_rand(1, 100) > 40) continue;
    $limiteMin = sumar($v1['salida'], 24 * 60);
    $limiteMax = sumar($v1['salida'], 4 * 24 * 60);
    foreach ($plan as $j => $v2) {
        if ($v2['origen'] !== $v1['destino'] || in_array($v2['destino'], CENTROS, true) || $v2['destino'] === $v1['origen']) continue;
        if ($v2['salida'] < $limiteMin || $v2['salida'] > $limiteMax) continue;
        $peso = min(peso_para($v1['vehiculo']), peso_para($v2['vehiculo']));
        if ($plan[$i]['carga'] + $peso > $v1['vehiculo']['capacidad'] || $plan[$j]['carga'] + $peso > $v2['vehiculo']['capacidad']) continue;
        $nro = crear_envio($pdo, $destinos, $remitentes, fecha_envio($v1['salida'], $hoy), $peso, $v1['origen'], $v2['destino']);
        asignar($pdo, $v1['id'], $nro);   // tramo 1
        asignar($pdo, $v2['id'], $nro);   // tramo 2: el trigger controla que salga de donde llega el 1
        $plan[$i]['carga'] += $peso; $plan[$j]['carga'] += $peso;
        $plan[$i]['envios'][] = $nro;  $plan[$j]['envios'][] = $nro;
        $datosEnvio[$nro] = ['tramos' => [$i, $j]];
        $trasbordos++;
        break;
    }
}

// Envíos recibidos hoy que todavía no tienen viaje.
for ($k = 0; $k < ENVIOS_SIN_VIAJE; $k++) {
    $o = azar($sucursales);
    do { $d = azar($sucursales); } while ($d === $o);
    $nro = crear_envio($pdo, $destinos, $remitentes, $hoy, mt_rand(50, 2000) / 100, $o, $d);
    $datosEnvio[$nro] = ['tramos' => []];
}
echo '  Envíos: ' . count($datosEnvio) . " ($trasbordos con trasbordo, " . ENVIOS_SIN_VIAJE . " sin viaje).\n";

// ------------------------------------------------------------------ 5) salidas reales
// En orden de salida: así nunca se rompe un trasbordo al mover las fechas.
foreach ($plan as $v) {
    ejecutar($pdo, 'UPDATE viaje SET fecha_salida = :s WHERE viaje_id = :id', [':s' => $v['salida'], ':id' => $v['id']]);
}

// ------------------------------------------------------------------ 6) hora del "Recibido"
// El trigger lo grabó con la hora actual: se pasa a la hora real de recepción (modo mantenimiento).
foreach ($datosEnvio as $nro => $d) {
    $fecha = (string)valor($pdo, 'SELECT fecha_envio FROM envio WHERE nro_envio = ?', [$nro]);
    $tope  = $ahora;
    if ($d['tramos']) $tope = min($tope, sumar($plan[$d['tramos'][0]]['salida'], -30));   // antes de que salga
    $desde = "$fecha 07:00:00";
    $recibido = $tope <= $desde ? sumar($tope, -mt_rand(5, 25))
                                : date('Y-m-d H:i:s', mt_rand(strtotime($desde), strtotime(min($tope, "$fecha 20:00:00"))));
    if (substr($recibido, 0, 10) < $fecha) $recibido = "$fecha 00:05:00";
    ejecutar($pdo, "UPDATE historial_estado SET fecha_hora = :f WHERE nro_envio = :n AND estado = 'RECIBIDO'",
             [':f' => $recibido, ':n' => $nro]);
}
$pdo->exec('SET @mantenimiento = NULL');

// ------------------------------------------------------------------ 7) viajes que ya salieron
// Por cada viaje, en orden: "En tránsito" al salir y, si ya pasó su llegada, se cierra con el
// procedimiento (que deja los envíos "En sucursal" en el destino).
function estado(PDO $pdo, int $nro, string $suc, string $estado, string $fecha, string $obs): void {
    ejecutar($pdo, 'CALL procedimiento_registrar_estado_envio(:n, :s, :e, :f, :o, @id)',
             [':n' => $nro, ':s' => $suc, ':e' => $estado, ':f' => $fecha, ':o' => $obs]);
}
$llegadas = [];   // índice de viaje => hora de llegada real
$enCurso  = [];
foreach ($plan as $i => $v) {
    if ($v['salida'] > $ahora) continue;
    foreach ($v['envios'] as $nro) estado($pdo, $nro, $v['origen'], 'EN_TRANSITO', $v['salida'], "Salió en el viaje {$v['id']}");
    $llegada = sumar($v['salida'], mt_rand(14 * 60, 23 * 60));   // antes de la llegada estimada (24 h)
    if ($llegada <= $ahora) {
        ejecutar($pdo, 'CALL procedimiento_cerrar_viaje(:id, :l, @n)', [':id' => $v['id'], ':l' => $llegada]);
        $llegadas[$i] = $llegada;
    } else {
        $enCurso[] = $i;
    }
}

// Entregas: el 80 % de los envíos que llegaron a su destino final ya se entregó (2 a 10 h después).
$entregados = 0;
foreach ($datosEnvio as $nro => $d) {
    if (!$d['tramos']) continue;
    $ultimo = end($d['tramos']);
    if (!isset($llegadas[$ultimo]) || mt_rand(1, 100) > 80) continue;
    $hora = sumar($llegadas[$ultimo], mt_rand(120, 600));
    if ($hora > $ahora) continue;
    estado($pdo, $nro, $plan[$ultimo]['destino'], 'ENTREGADO', $hora, 'Entregado al destinatario');
    $entregados++;
}

// ------------------------------------------------------------------ 8) incidentes y demoras
// CAMBIAR: incidentes de ejemplo (tipo del catálogo tipo_incidente, descripción).
$incidentes = [['DEMORA', 'Corte de ruta por obras'], ['AVERIA', 'Pinchadura, se cambió la rueda'],
               ['OTRO', 'Control policial en ruta'], ['DEMORA', 'Tránsito pesado en el acceso a la ciudad']];
$conIncidente = array_slice(array_merge($enCurso, array_keys($llegadas)), 0, 6);
foreach ($conIncidente as $n => $i) {
    $v = $plan[$i];
    $hasta = $llegadas[$i] ?? $ahora;
    $fecha = date('Y-m-d H:i:s', mt_rand(strtotime($v['salida']), strtotime($hasta)));
    [$tipo, $descripcion] = $incidentes[$n % count($incidentes)];
    ejecutar($pdo, 'INSERT INTO incidente (viaje_id, fecha_hora, tipo_incidente, descripcion) VALUES (:v, :f, :t, :d)',
             [':v' => $v['id'], ':f' => $fecha, ':t' => $tipo, ':d' => $descripcion]);
    // En los viajes en curso con demora, sus envíos pasan a "Demorado".
    if (!isset($llegadas[$i]) && $tipo === 'DEMORA') {
        foreach ($v['envios'] as $nro) estado($pdo, $nro, $v['origen'], 'DEMORADO', $fecha, 'Demora en ruta: ' . $descripcion);
    }
}

// ------------------------------------------------------------------ 9) limpieza final y resumen
$pdo->exec("DELETE FROM auditoria WHERE fecha_hora >= '$inicioCorrida' AND usuario IN ('carga_de_datos', 'root@localhost')");
$pdo->query('ANALYZE TABLE envio, historial_estado, viaje, viaje_envio')->fetchAll();   // estadísticas al día

echo "  Viajes cerrados: " . count($llegadas) . ", en curso: " . count($enCurso) . ", programados: "
   . count(array_filter($plan, fn($v) => $v['salida'] > $ahora)) . ".\n";
echo "  Envíos entregados: $entregados. Incidentes: " . count($conIncidente) . ".\n";

// Para la demo de "Cerrar viaje": viajes en curso que salieron hoy, con envíos.
$demo = array_values(array_filter($enCurso, fn($i) => substr($plan[$i]['salida'], 0, 10) === $hoy && $plan[$i]['envios']));
if ($demo) {
    echo "\nPara la demo de \"Cerrar viaje\" (salieron hoy y están en curso):\n";
    foreach (array_slice($demo, 0, 3) as $i) {
        $v = $plan[$i];
        echo "  Viaje {$v['id']}: {$v['origen']} -> {$v['destino']}, salió " . substr($v['salida'], 11, 5)
           . ', envíos ' . implode(', ', $v['envios']) . "\n";
    }
}
echo "\nListo.\n";
