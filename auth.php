<?php
// Inicio de sesión y registro de usuarios.
// Recibe POST con: accion ("login" | "registro"), usuario, password[, confirmar]
header('Content-Type: application/json; charset=utf-8');

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

function responder(int $codigo, array $datos): void {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}

$accion    = $_POST['accion']    ?? '';
$usuario   = trim($_POST['usuario'] ?? '');
$password  = $_POST['password']  ?? '';
$confirmar = $_POST['confirmar'] ?? '';

if ($accion !== 'login' && $accion !== 'registro') {
    responder(400, ['ok' => false, 'mensaje' => 'Acción no válida.']);
}

// Validación en el servidor (misma lógica que login.js, pero esta es la que cuenta)
$errores = [];

if ($usuario === '') {
    $errores['usuario'] = 'El usuario es obligatorio.';
} elseif ($accion === 'registro' && !preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $usuario)) {
    $errores['usuario'] = 'De 3 a 30 caracteres: letras, números, punto o guion bajo.';
}

if ($password === '') {
    $errores['password'] = 'La contraseña es obligatoria.';
} elseif ($accion === 'registro' && (strlen($password) < 8 || strlen($password) > 72)) {
    $errores['password'] = 'La contraseña debe tener entre 8 y 72 caracteres.';
}

if ($accion === 'registro' && !isset($errores['password']) && $confirmar !== $password) {
    $errores['confirmar'] = 'Las contraseñas no coinciden.';
}

if ($errores) {
    responder(422, ['ok' => false, 'errores' => $errores]);
}

try {
    $cfg = require '/srv/herreria_config/db.php';
    $pdo = new PDO(
        "mysql:host={$cfg['host']};dbname={$cfg['db']};charset=utf8mb4",
        $cfg['user'],
        $cfg['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    if ($accion === 'registro') {
        $stmt = $pdo->prepare('SELECT 1 FROM usuarios WHERE usuario = :usuario');
        $stmt->execute([':usuario' => $usuario]);
        if ($stmt->fetchColumn()) {
            responder(409, ['ok' => false, 'errores' => ['usuario' => 'Ese usuario ya existe.']]);
        }

        $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, password_hash) VALUES (:usuario, :hash)');
        $stmt->execute([
            ':usuario' => $usuario,
            ':hash'    => password_hash($password, PASSWORD_DEFAULT),
        ]);
        $id  = (int) $pdo->lastInsertId();
        $rol = 'cliente';
    } else {
        $stmt = $pdo->prepare('SELECT id, password_hash, rol FROM usuarios WHERE usuario = :usuario');
        $stmt->execute([':usuario' => $usuario]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        // Mismo mensaje si no existe el usuario o si la contraseña es incorrecta
        if (!$fila || !password_verify($password, $fila['password_hash'])) {
            responder(401, ['ok' => false, 'mensaje' => 'Usuario o contraseña incorrectos.']);
        }
        $id  = (int) $fila['id'];
        $rol = $fila['rol'];
    }

    // Nueva sesión para evitar fijación de sesión
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = $id;
    $_SESSION['usuario']    = $usuario;
    $_SESSION['rol']        = $rol;

    // Por ahora todos regresan al inicio; el panel de administrador se agregará después
    responder(200, ['ok' => true, 'rol' => $rol, 'redirigir' => 'index.html']);
} catch (Throwable $e) {
    error_log('auth.php: ' . $e->getMessage());
    responder(500, ['ok' => false, 'mensaje' => 'No se pudo procesar la solicitud.']);
}
