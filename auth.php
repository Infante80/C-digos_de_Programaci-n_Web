<?php
// Inicio de sesión y registro de usuarios.
// Recibe POST con: accion ("login" | "registro"), usuario, password[, confirmar]
header('Content-Type: application/json; charset=utf-8');

// Se permiten 3 intentos erróneos; al pasar de 3 (el 4.º) se bloquea
const MAX_FALLOS      = 3;
const MINUTOS_BLOQUEO = 45;

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
        // Ningún usuario real pasa de 30 caracteres
        if (mb_strlen($usuario, 'UTF-8') > 30) {
            responder(401, ['ok' => false, 'mensaje' => 'Usuario o contraseña incorrectos.']);
        }

        // Los intentos se cuentan por usuario + IP, en la BD (borrar cookies no los reinicia)
        $clave = [':usuario' => $usuario, ':ip' => $_SERVER['REMOTE_ADDR']];

        // La fila queda bloqueada hasta el commit: peticiones simultáneas no se saltan el límite
        $pdo->beginTransaction();
        $pdo->prepare(
            'INSERT INTO intentos_login (usuario, ip) VALUES (:usuario, :ip)
             ON DUPLICATE KEY UPDATE fallos = fallos'
        )->execute($clave);

        $stmt = $pdo->prepare(
            'SELECT fallos,
                    TIMESTAMPDIFF(SECOND, NOW(), bloqueado_hasta) AS restante,
                    ultimo_fallo < NOW() - INTERVAL ' . MINUTOS_BLOQUEO . ' MINUTE AS vencido
             FROM intentos_login WHERE usuario = :usuario AND ip = :ip FOR UPDATE'
        );
        $stmt->execute($clave);
        $intento = $stmt->fetch(PDO::FETCH_ASSOC);

        if ((int) $intento['restante'] > 0) {
            $pdo->commit();
            $minutos = (int) ceil($intento['restante'] / 60);
            responder(429, ['ok' => false, 'mensaje' => "Demasiados intentos. Vuelve a intentarlo en $minutos min."]);
        }

        $stmt = $pdo->prepare('SELECT id, usuario, password_hash, rol FROM usuarios WHERE usuario = :usuario');
        $stmt->execute([':usuario' => $usuario]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        // Mismo mensaje si no existe el usuario o si la contraseña es incorrecta
        if (!$fila || !password_verify($password, $fila['password_hash'])) {
            // Los fallos de hace más de 45 minutos ya no cuentan
            $fallos   = $intento['vencido'] ? 1 : $intento['fallos'] + 1;
            $bloquear = $fallos > MAX_FALLOS;
            $hasta    = $bloquear ? 'NOW() + INTERVAL ' . MINUTOS_BLOQUEO . ' MINUTE' : 'NULL';

            $pdo->prepare(
                "UPDATE intentos_login SET fallos = :fallos, ultimo_fallo = NOW(), bloqueado_hasta = $hasta
                 WHERE usuario = :usuario AND ip = :ip"
            )->execute($clave + [':fallos' => $bloquear ? 0 : $fallos]);
            $pdo->commit();

            if ($bloquear) {
                responder(429, ['ok' => false, 'mensaje' => 'Demasiados intentos. Vuelve a intentarlo en ' . MINUTOS_BLOQUEO . ' min.']);
            }
            responder(401, ['ok' => false, 'mensaje' => 'Usuario o contraseña incorrectos.']);
        }

        // Acceso correcto: se borra el historial de fallos
        $pdo->prepare('DELETE FROM intentos_login WHERE usuario = :usuario AND ip = :ip')->execute($clave);
        $pdo->commit();

        $id      = (int) $fila['id'];
        $usuario = $fila['usuario']; // como se registró, no como se tecleó
        $rol     = $fila['rol'];
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
