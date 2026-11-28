<?php
header('Content-Type: application/json; charset=utf-8');

function responder(int $codigo, array $datos): void {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(405, ['ok' => false, 'mensaje' => 'Método no permitido.']);
}

$nombre  = trim($_POST['nombre']  ?? '');
$correo  = trim($_POST['email']   ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

// Validación en el servidor (misma lógica que el JS, pero esta es la que cuenta)
$errores = [];

if ($nombre === '') {
    $errores['nombre'] = 'El nombre es obligatorio.';
} elseif (!preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ'\s]{2,50}$/u", $nombre)) {
    $errores['nombre'] = 'El nombre solo debe contener letras y espacios.';
}

if ($correo === '') {
    $errores['email'] = 'El correo es obligatorio.';
} elseif (strlen($correo) > 150 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores['email'] = 'Ingresa un correo electrónico válido.';
}

$largo = mb_strlen($mensaje, 'UTF-8');
if ($mensaje === '') {
    $errores['mensaje'] = 'El mensaje es obligatorio.';
} elseif ($largo < 10 || $largo > 500) {
    $errores['mensaje'] = 'El mensaje debe tener entre 10 y 500 caracteres.';
}

if ($errores) {
    responder(422, ['ok' => false, 'errores' => $errores]);
}

// Guardar con consulta preparada
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

    $stmt = $pdo->prepare(
        'INSERT INTO cotizaciones (nombre, correo, mensaje) VALUES (:nombre, :correo, :mensaje)'
    );
    $stmt->execute([
        ':nombre'  => $nombre,
        ':correo'  => $correo,
        ':mensaje' => $mensaje,
    ]);

    responder(200, ['ok' => true, 'mensaje' => 'Solicitud enviada.']);
} catch (Throwable $e) {
    error_log('guardar.php: ' . $e->getMessage());
    responder(500, ['ok' => false, 'mensaje' => 'No se pudo guardar la solicitud.']);
}
