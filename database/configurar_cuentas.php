<?php
/*
 * Pone contraseñas a las cuentas de la aplicación (script 016) y escribe config/credenciales.php.
 *
 * Se corre UNA vez por consola, después de 016 (y cada vez que se quieran cambiar las claves):
 *     php database/configurar_cuentas.php
 * Pide la contraseña de root de MariaDB (Enter si root no tiene). También se puede pasar en la
 * variable de entorno LOGITRACK_ROOT_CLAVE.
 *
 * Por qué así: las contraseñas no pueden quedar en el repositorio (cualquiera que lo lea entraría
 * a la base). Se generan al azar (random_bytes) y quedan solo en config/credenciales.php, que
 * está en .gitignore y bloqueado en la web por el .htaccess.
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

$archivo = __DIR__ . '/../config/credenciales.php';
$contenido = "<?php\n// Generado por database/configurar_cuentas.php. NO versionar (está en .gitignore).\n"
           . 'return ' . var_export($credenciales, true) . ";\n";
if (file_put_contents($archivo, $contenido) === false) {
    fwrite(STDERR, "No se pudo escribir $archivo\n");
    exit(1);
}
echo "Listo: config/credenciales.php actualizado.\n";
