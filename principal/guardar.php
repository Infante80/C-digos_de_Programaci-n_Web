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
$telefono = trim($_POST['telefono'] ?? '');

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

// Se aceptan espacios, guiones y paréntesis; en la BD solo se guardan los dígitos
$digitos = preg_replace('/\D/', '', $telefono);
if ($telefono === '') {
    $errores['telefono'] = 'El teléfono es obligatorio.';
} elseif (!preg_match('/^[0-9\s()-]+$/', $telefono) || strlen($digitos) !== 10) {
    $errores['telefono'] = 'Ingresa un teléfono de 10 dígitos.';
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
        'INSERT INTO cotizaciones (nombre, correo, telefono, mensaje)
         VALUES (:nombre, :correo, :telefono, :mensaje)'
    );
    $stmt->execute([
        ':nombre'  => $nombre,
        ':correo'  => $correo,
        ':telefono' => $digitos,
        ':mensaje' => $mensaje,
    ]);

    responder(200, ['ok' => true, 'mensaje' => 'Solicitud enviada.']);
} catch (Throwable $e) {
    error_log('guardar.php: ' . $e->getMessage());
    responder(500, ['ok' => false, 'mensaje' => 'No se pudo guardar la solicitud.']);
}
