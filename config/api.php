<?php
/*
 * Base común de todas las APIs (capa Controlador).
 *
 * Cada api/*.php empieza con:
 *     require_once '../config/api.php';
 * y con eso ya tiene sesión verificada, conexión ($pdo), respuestas JSON y manejo de errores.
 *
 * Manejo de errores: cualquier excepción que no se atrape termina en manejar_error(), que
 * responde un JSON {"error": "..."} con el código HTTP adecuado y nunca muestra detalles internos
 * (rutas, SQL) al usuario. El detalle técnico queda en el log de PHP.
 */

ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/session.php';
verificar_sesion();
require_once __DIR__ . '/db.php';

/** Error de datos de entrada: termina en un 400 con el mensaje tal cual. */
class ValidacionException extends Exception {}

// ---------------------------------------------------------------------------
// Respuestas
// ---------------------------------------------------------------------------

function responder($datos, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($datos);
    exit;
}

function error_api(string $mensaje, int $codigo = 400): void
{
    responder(['error' => $mensaje], $codigo);
}

/** Cuerpo JSON de la petición (POST / PUT / DELETE) como array. */
function leer_json(): array
{
    $datos = json_decode(file_get_contents('php://input'), true);
    return is_array($datos) ? $datos : [];
}

// ---------------------------------------------------------------------------
// Validaciones: devuelven el valor limpio o lanzan ValidacionException
// ---------------------------------------------------------------------------

function campo_texto(array $d, string $campo, string $etiqueta, int $max, bool $obligatorio = true): ?string
{
    $valor = trim((string)($d[$campo] ?? ''));
    if ($valor === '') {
        if ($obligatorio) throw new ValidacionException("$etiqueta es obligatorio.");
        return null;
    }
    if (mb_strlen($valor) > $max) {
        throw new ValidacionException("$etiqueta no puede superar los $max caracteres.");
    }
    return $valor;
}

function campo_entero(array $d, string $campo, string $etiqueta, int $min = 1, int $max = PHP_INT_MAX, bool $obligatorio = true): ?int
{
    $valor = trim((string)($d[$campo] ?? ''));
    if ($valor === '') {
        if ($obligatorio) throw new ValidacionException("$etiqueta es obligatorio.");
        return null;
    }
    if (!preg_match('/^-?\d+$/', $valor) || (int)$valor < $min || (int)$valor > $max) {
        throw new ValidacionException("$etiqueta debe ser un número entero entre $min y $max.");
    }
    return (int)$valor;
}

function campo_email(array $d, string $campo, string $etiqueta, bool $obligatorio = false): ?string
{
    $valor = campo_texto($d, $campo, $etiqueta, 150, $obligatorio);
    if ($valor !== null && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
        throw new ValidacionException("$etiqueta no tiene un formato válido.");
    }
    return $valor;
}

function campo_telefono(array $d, string $campo, string $etiqueta, bool $obligatorio = false): ?string
{
    $valor = campo_texto($d, $campo, $etiqueta, 30, $obligatorio);
    if ($valor !== null && !preg_match('/^\+?[0-9 ()-]{6,30}$/', $valor)) {
        throw new ValidacionException("$etiqueta solo puede tener números, espacios, guiones, paréntesis y un + inicial.");
    }
    return $valor;
}

// ---------------------------------------------------------------------------
// Errores de la base de datos
// ---------------------------------------------------------------------------

/** Código de error de MariaDB/MySQL (1062, 1451, ...) de una PDOException. */
function codigo_mysql(PDOException $e): int
{
    return (int)($e->errorInfo[1] ?? 0);
}

/** Traduce los errores de la base más comunes a mensajes para el usuario. */
function manejar_error(Throwable $e): void
{
    if ($e instanceof ValidacionException) {
        error_api($e->getMessage(), 400);
    }
    if ($e instanceof PDOException) {
        switch (codigo_mysql($e)) {
            case 1062: error_api('Ya existe un registro con esos datos.', 409);
            case 1451: error_api('No se puede eliminar o modificar: hay otros registros que dependen de este.', 409);
            case 1452: error_api('Alguno de los datos relacionados no existe (revisá los códigos elegidos).', 400);
            case 1048: error_api('Falta un dato obligatorio.', 400);
            case 1406: error_api('Alguno de los datos es demasiado largo.', 400);
            case 4025: error_api('Alguno de los datos no cumple las reglas de la base.', 400);
            case 1644: // SIGNAL SQLSTATE '45000' de un trigger o procedimiento: el mensaje es nuestro
                error_api($e->errorInfo[2] ?? 'Operación rechazada por la base de datos.', 400);
        }
    }
    error_log('[LogiTrack API] ' . $e);
    error_api('Ocurrió un error interno. Intentá de nuevo o avisá al administrador.', 500);
}

set_exception_handler('manejar_error');
