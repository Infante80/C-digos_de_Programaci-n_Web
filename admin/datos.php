<?php
// Datos del panel, leídos de la base de datos. Lo incluye panel.php.
$cotizaciones = $entregas = $garantias = $materiales = [];
$errorDatos = false;

try {
    $cfg = require '/srv/herreria_config/db.php';
    $pdo = new PDO(
        "mysql:host={$cfg['host']};dbname={$cfg['db']};charset=utf8mb4",
        $cfg['user'],
        $cfg['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $cotizaciones = $pdo->query(
        'SELECT nombre, correo, telefono, mensaje, recibida_en
         FROM cotizaciones WHERE atendida = 0 ORDER BY recibida_en DESC'
    )->fetchAll();

    $entregas = $pdo->query(
        'SELECT cliente, trabajo, fecha_entrega
         FROM trabajos WHERE entregado_en IS NULL ORDER BY fecha_entrega'
    )->fetchAll();

    $garantias = $pdo->query(
        'SELECT cliente, trabajo, entregado_en, garantia_hasta
         FROM trabajos WHERE entregado_en IS NOT NULL AND garantia_hasta IS NOT NULL
         ORDER BY garantia_hasta'
    )->fetchAll();

    $materiales = $pdo->query(
        'SELECT nombre, cantidad, minimo, unidad FROM materiales ORDER BY nombre'
    )->fetchAll();
} catch (Throwable $e) {
    error_log('admin/datos.php: ' . $e->getMessage());
    $errorDatos = true;
}
