<?php
/*
 * Crea las cuentas de la aplicación con sus roles y permisos, les pone contraseña y escribe
 * config/credenciales.php. Es el único paso de instalación después de importar el volcado.
 *
 *     C:\xampp\php\php database\configurar_cuentas.php
 *
 * Pide la contraseña de root de MariaDB (Enter si root no tiene). También se puede pasar en la
 * variable de entorno LOGITRACK_ROOT_CLAVE. Se puede volver a correr (por ejemplo, para cambiar
 * las contraseñas): vuelve a crear las cuentas desde cero.
 *
 * Por qué hace falta: los usuarios, roles y permisos (GRANT) se guardan en la base del sistema
 * `mysql`, no en `logitrack`, así que el volcado database/logitrack.sql no los trae.
 *
 *  1. Ejecuta database/cambios/016_cuentas_y_roles.sql: roles, permisos y cuentas (bloqueadas).
 *  2. Da el permiso sobre la función del 017 (funcion_carga_viaje_bloqueando).
 *  3. Pone a cada cuenta una contraseña al azar (random_bytes) y la desbloquea.
 *  4. Guarda las contraseñas en config/credenciales.php (está en .gitignore y el .htaccess lo
 *     bloquea en la web): nunca quedan en el repositorio.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo por consola.');
}

$claveRoot = getenv('LOGITRACK_ROOT_CLAVE');
if ($claveRoot === false) {
    echo 'Contraseña de root de MariaDB (Enter si no tiene): ';
    $claveRoot = trim((string)fgets(STDIN));
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=logitrack;charset=utf8mb4', 'root', $claveRoot,
                   [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    fwrite(STDERR, "No se pudo conectar como root: {$e->getMessage()}\n");
    exit(1);
}

try {
    // 1) Script 016, sentencia por sentencia (no tiene procedimientos: se puede cortar en cada ';').
    $sql = file_get_contents(__DIR__ . '/cambios/016_cuentas_y_roles.sql');
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);            // comentarios de línea
    foreach (preg_split('/;\s*$/m', $sql) as $sentencia) {
        if (trim($sentencia) !== '') $pdo->exec($sentencia);
    }
    echo "  Roles, permisos y cuentas creados (016).\n";

    // 2) El permiso que agregó el 017 (su función nueva la usa PHP con la cuenta del operador).
    $pdo->exec('GRANT EXECUTE ON FUNCTION logitrack.funcion_carga_viaje_bloqueando TO rol_operador');
    echo "  Permiso sobre funcion_carga_viaje_bloqueando (017).\n";

    // 3) Contraseñas al azar.
    $cuentas = [
        'login'    => 'logitrack_login',
        'operador' => 'logitrack_operador',
        'admin'    => 'logitrack_admin',
    ];
    $credenciales = [];
    foreach ($cuentas as $rol => $usuario) {
        $clave = bin2hex(random_bytes(16));   // 32 caracteres al azar
        // ALTER USER no acepta parámetros (?): la clave va escapada con quote().
        $pdo->exec("ALTER USER '$usuario'@'localhost' IDENTIFIED BY " . $pdo->quote($clave) . ' ACCOUNT UNLOCK');
        $credenciales[$rol] = ['usuario' => $usuario, 'clave' => $clave];
        echo "  $usuario: contraseña nueva, cuenta desbloqueada.\n";
    }
} catch (PDOException $e) {
    fwrite(STDERR, "Error: {$e->getMessage()}\n¿Se importó database/logitrack.sql antes?\n");
    exit(1);
}

// 4) Credenciales para config/conexion.php.
$archivo = __DIR__ . '/../config/credenciales.php';
$contenido = "<?php\n// Generado por database/configurar_cuentas.php. NO versionar (está en .gitignore).\n"
           . 'return ' . var_export($credenciales, true) . ";\n";
if (file_put_contents($archivo, $contenido) === false) {
    fwrite(STDERR, "No se pudo escribir $archivo\n");
    exit(1);
}
echo "Listo: config/credenciales.php actualizado.\n";
