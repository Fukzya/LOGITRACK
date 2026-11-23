<?php
/* Controlador de usuarios del sistema (solo admin): valida, llama al modelo Usuario y responde JSON. */
require_once '../config/api.php';
require_once '../modelos/Usuario.php';

if (!es_admin()) {
    error_api('Acceso denegado: solo un administrador puede gestionar usuarios.', 403);
}

$usuarios = new Usuario($pdo);
const ROLES = ['admin', 'operador'];

function password_nueva(array $d): string
{
    $p = (string)($d['password'] ?? '');
    if (mb_strlen($p) < 8)   throw new ValidacionException('La contraseña debe tener al menos 8 caracteres.');
    if (mb_strlen($p) > 72)  throw new ValidacionException('La contraseña no puede superar los 72 caracteres.');
    return $p;
}

switch ($_SERVER['REQUEST_METHOD']) {

    case 'GET':
        responder($usuarios->listar(params_listado('usuario_id')));

    case 'POST':
        $d        = leer_json();
        $username = campo_codigo($d, 'username', 'El usuario', '/^[a-zA-Z0-9_.]{3,50}$/',
                                 'de 3 a 50 letras, números, punto o guion bajo', false);
        $password = password_nueva($d);
        $rol      = campo_opcion($d, 'rol', 'El rol', ROLES);
        $id = intentar(fn() => $usuarios->crear($username, $password, $rol), [1062 => "El usuario $username ya existe."]);
        responder(['mensaje' => 'Usuario creado.', 'id' => $id], 201);

    // Un PUT cambia una sola cosa: el rol, la contraseña o el estado (activo).
    case 'PUT':
        $d      = leer_json();
        $id     = campo_entero($d, 'usuario_id', 'El usuario');
        $propio = $id === (int)$_SESSION['usuario_id'];

        if (array_key_exists('rol', $d)) {
            if ($propio) error_api('No podés cambiar tu propio rol.', 403);
            $ok  = $usuarios->cambiarRol($id, campo_opcion($d, 'rol', 'El rol', ROLES));
            $msg = 'Rol actualizado.';
        } elseif (array_key_exists('password', $d)) {
            $ok  = $usuarios->cambiarPassword($id, password_nueva($d));
            $msg = 'Contraseña actualizada.';
        } elseif (array_key_exists('activo', $d)) {
            $activo = campo_bool($d, 'activo') === 1;
            if ($propio && !$activo) error_api('No podés desactivarte a vos mismo.', 403);
            $ok  = $usuarios->cambiarActivo($id, $activo);
            $msg = $activo ? 'Usuario activado.' : 'Usuario desactivado.';
        } else {
            error_api('Indicá qué cambiar: rol, password o activo.', 400);
        }
        if (!$ok) error_api('No existe ese usuario.', 404);
        responder(['mensaje' => $msg]);

    case 'DELETE':
        $id = campo_entero(leer_json(), 'usuario_id', 'El usuario');
        if ($id === (int)$_SESSION['usuario_id']) error_api('No podés desactivarte a vos mismo.', 403);
        if (!$usuarios->cambiarActivo($id, false)) error_api('No existe ese usuario.', 404);
        responder(['mensaje' => 'Usuario desactivado.']);

    default:
        error_api('Método no permitido.', 405);
}
