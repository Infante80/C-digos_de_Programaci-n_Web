<?php
// Panel de administración. Solo lo puede ver un usuario con rol "admin".
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => !empty($_SERVER['HTTPS']),
]);
session_start();

// Sin caché: tras cerrar sesión, el botón "atrás" no debe mostrar el panel
header('Cache-Control: no-store');

// La protección va en el servidor, antes de enviar cualquier contenido
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login/login.html');
    exit;
}
if (($_SESSION['rol'] ?? '') !== 'admin') {
    header('Location: ../index.html');
    exit;
}

// "Hoy" según la hora de Jalisco; sin esto PHP puede usar UTC y adelantar un día
date_default_timezone_set('America/Mexico_City');

require __DIR__ . '/datos.php';

// Escapa texto antes de ponerlo en el HTML
function e(?string $texto): string {
    return htmlspecialchars($texto ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Recibe una fecha de la BD ("2026-01-31") o "today"
function fecha(?string $f): string {
    return $f === null ? '—' : (new DateTimeImmutable($f))->format('d/m/Y');
}

// 12.00 se muestra como 12; 2.50 como 2.5
function numero(string $n): string {
    return (string) (float) $n;
}

// Fila que se muestra cuando una tabla no tiene registros
function vacia(array $filas, int $columnas): string {
    return $filas ? '' : '<tr><td colspan="' . $columnas . '">Sin registros.</td></tr>';
}

// Días desde hoy hasta la fecha (negativo si ya pasó)
function diasPara(string $f): int {
    return (int) (new DateTimeImmutable('today'))->diff(new DateTimeImmutable($f))->format('%r%a');
}

function etiqueta(string $tipo, string $texto): string {
    return '<span class="estado ' . $tipo . '">' . e($texto) . '</span>';
}

function estadoEntrega(string $f): string {
    $d = diasPara($f);
    if ($d < 0)  return etiqueta('mal', 'Atrasado');
    if ($d <= 7) return etiqueta('alerta', $d === 0 ? 'Hoy' : "En $d " . ($d === 1 ? 'día' : 'días'));
    return etiqueta('bien', 'En tiempo');
}

function estadoGarantia(string $fin): string {
    $d = diasPara($fin);
    if ($d < 0)   return etiqueta('mal', 'Vencida');
    if ($d <= 30) return etiqueta('alerta', 'Por vencer');
    return etiqueta('bien', 'Vigente');
}

$entregasSemana  = count(array_filter($entregas, fn($t) => diasPara($t['fecha_entrega']) <= 7));
$porVencer       = count(array_filter($garantias, fn($g) => diasPara($g['garantia_hasta']) >= 0 && diasPara($g['garantia_hasta']) <= 30));
$materialesBajos = count(array_filter($materiales, fn($m) => $m['cantidad'] <= $m['minimo']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Big+Shoulders+Display:wght@700;900&family=Archivo:wght@400;500;600&display=swap" rel="stylesheet">
    <title>Panel de administración · Herrería Hernández</title>
    <link rel="icon" href="../img/herreria-icono.png?v=2" type="image/png">
    <link rel="stylesheet" href="../principal/style.css">
    <link rel="stylesheet" href="admin.css">
</head>
<body>

    <nav aria-label="Administración">
        <div class="wrap">
            <a class="logo" href="../index.html">Herreria Hernández</a>
            <div class="panel-acciones">
                <a href="../index.html">Ver sitio</a>
                <button type="button" class="btn small" id="salir">Cerrar sesión</button>
            </div>
        </div>
    </nav>

    <main class="wrap panel">
        <header class="panel-titulo">
            <h1>¡Hola David!</h1>
            <p>Resumen del taller al <?= fecha('today') ?>.</p>
            <?php if ($errorDatos): ?>
                <p class="panel-nota" role="alert">No se pudo leer la base de datos. Las tablas pueden aparecer incompletas.</p>
            <?php endif; ?>
        </header>

        <ul class="resumen">
            <li><a href="#cotizar"><b><?= count($cotizaciones) ?></b><span>Trabajos por cotizar</span></a></li>
            <li><a href="#entregas"><b><?= $entregasSemana ?></b><span>Entregas atrasadas o en 7 días</span></a></li>
            <li><a href="#garantias"><b><?= $porVencer ?></b><span>Garantías por vencer en 30 días</span></a></li>
            <li><a href="#material"><b><?= $materialesBajos ?></b><span>Materiales por reponer</span></a></li>
        </ul>

        <section id="cotizar">
            <h2>Por cotizar</h2>
            <div class="tabla">
                <table>
                    <thead><tr><th>Recibida</th><th>Cliente</th><th>Teléfono</th><th>Correo</th><th>Mensaje</th></tr></thead>
                    <tbody>
                    <?= vacia($cotizaciones, 5) ?>
                    <?php foreach ($cotizaciones as $c): ?>
                        <tr>
                            <td><?= fecha($c['recibida_en']) ?></td>
                            <td><?= e($c['nombre']) ?></td>
                            <td><?= $c['telefono'] ? '<a href="tel:' . e($c['telefono']) . '">' . e($c['telefono']) . '</a>' : '—' ?></td>
                            <td><a href="mailto:<?= e($c['correo']) ?>"><?= e($c['correo']) ?></a></td>
                            <td class="mensaje"><?= e($c['mensaje']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="entregas">
            <h2>Por entregar</h2>
            <div class="tabla">
                <table>
                    <thead><tr><th>Cliente</th><th>Trabajo</th><th>Fecha de entrega</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?= vacia($entregas, 4) ?>
                    <?php foreach ($entregas as $t): ?>
                        <tr>
                            <td><?= e($t['cliente']) ?></td>
                            <td><?= e($t['trabajo']) ?></td>
                            <td><?= fecha($t['fecha_entrega']) ?></td>
                            <td><?= estadoEntrega($t['fecha_entrega']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="garantias">
            <h2>Garantías</h2>
            <div class="tabla">
                <table>
                    <thead><tr><th>Cliente</th><th>Trabajo</th><th>Entregado</th><th>Garantía hasta</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?= vacia($garantias, 5) ?>
                    <?php foreach ($garantias as $g): ?>
                        <tr>
                            <td><?= e($g['cliente']) ?></td>
                            <td><?= e($g['trabajo']) ?></td>
                            <td><?= fecha($g['entregado_en']) ?></td>
                            <td><?= fecha($g['garantia_hasta']) ?></td>
                            <td><?= estadoGarantia($g['garantia_hasta']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section id="material">
            <h2>Material</h2>
            <div class="tabla">
                <table>
                    <thead><tr><th>Material</th><th>Existencia</th><th>Mínimo</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?= vacia($materiales, 4) ?>
                    <?php foreach ($materiales as $m): ?>
                        <tr>
                            <td><?= e($m['nombre']) ?></td>
                            <td><?= numero($m['cantidad']) ?> <?= e($m['unidad']) ?></td>
                            <td><?= numero($m['minimo']) ?> <?= e($m['unidad']) ?></td>
                            <td><?= $m['cantidad'] <= $m['minimo'] ? etiqueta('mal', 'Reponer') : etiqueta('bien', 'Suficiente') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script src="admin.js"></script>
</body>
</html>
