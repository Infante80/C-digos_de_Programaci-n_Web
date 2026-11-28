<?php
// GET: indica si hay una sesión iniciada, para mostrar el usuario en la página.
// POST: cierra la sesión.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vacía los datos, borra la cookie y destruye la sesión en el servidor
    $_SESSION = [];
    $cookie = session_get_cookie_params();
    unset($cookie['lifetime']);
    setcookie(session_name(), '', ['expires' => time() - 3600] + $cookie);
    session_destroy();
    echo json_encode(['logueado' => false]);
    exit;
}

echo json_encode(
    isset($_SESSION['usuario_id'])
        ? ['logueado' => true, 'usuario' => $_SESSION['usuario'], 'rol' => $_SESSION['rol']]
        : ['logueado' => false],
    JSON_UNESCAPED_UNICODE
);
