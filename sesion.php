<?php
// Indica si hay una sesión iniciada, para mostrar el usuario en la página.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

echo json_encode(
    isset($_SESSION['usuario_id'])
        ? ['logueado' => true, 'usuario' => $_SESSION['usuario'], 'rol' => $_SESSION['rol']]
        : ['logueado' => false],
    JSON_UNESCAPED_UNICODE
);
