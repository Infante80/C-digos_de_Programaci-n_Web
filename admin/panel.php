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

require __DIR__ . '/datos.php';

// Escapa texto antes de ponerlo en el HTML
function e(string $texto): string {
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

function fecha(DateTimeImmutable $f): string {
    return $f->format('d/m/Y');
}

// Días desde hoy hasta la fecha (negativo si ya pasó)
function diasPara(DateTimeImmutable $f): int {
    return (int) (new DateTimeImmutable('today'))->diff($f)->format('%r%a');
}

function etiqueta(string $tipo, string $texto): string {
    return '<span class="estado ' . $tipo . '">' . e($texto) . '</span>';
}

function estadoEntrega(DateTimeImmutable $f): string {
    $d = diasPara($f);
    if ($d < 0)  return etiqueta('mal', 'Atrasado');
    if ($d <= 7) return etiqueta('alerta', $d === 0 ? 'Hoy' : "En $d " . ($d === 1 ? 'día' : 'días'));
    return etiqueta('bien', 'En tiempo');
}

function estadoGarantia(DateTimeImmutable $fin): string {
    $d = diasPara($fin);
    if ($d < 0)   return etiqueta('mal', 'Vencida');
    if ($d <= 30) return etiqueta('alerta', 'Por vencer');
    return etiqueta('bien', 'Vigente');
}

// Primero lo más urgente
usort($entregas, fn($a, $b) => $a['entrega'] <=> $b['entrega']);
usort($garantias, fn($a, $b) => $a['fin'] <=> $b['fin']);

$entregasSemana  = count(array_filter($entregas, fn($t) => diasPara($t['entrega']) <= 7));
$porVencer       = count(array_filter($garantias, fn($g) => diasPara($g['fin']) >= 0 && diasPara($g['fin']) <= 30));
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
            <p>Resumen del taller al <?= fecha(new DateTimeImmutable('today')) ?>.</p>
            <p class="panel-nota">Los datos mostrados son de ejemplo. Aún no vienen de la base de datos.</p>
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
                    <thead><tr><th>Cliente</th><th>Trabajo</th><th>Solicitado</th></tr></thead>
                    <tbody>
                    <?php foreach ($cotizaciones as $c): ?>
                        <tr>
                            <td><?= e($c['cliente']) ?></td>
                            <td><?= e($c['trabajo']) ?></td>
                            <td><?= fecha($c['solicitado']) ?></td>
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
                    <?php foreach ($entregas as $t): ?>
                        <tr>
                            <td><?= e($t['cliente']) ?></td>
                            <td><?= e($t['trabajo']) ?></td>
                            <td><?= fecha($t['entrega']) ?></td>
                            <td><?= estadoEntrega($t['entrega']) ?></td>
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
                    <?php foreach ($garantias as $g): ?>
                        <tr>
                            <td><?= e($g['cliente']) ?></td>
                            <td><?= e($g['trabajo']) ?></td>
                            <td><?= fecha($g['entregado']) ?></td>
                            <td><?= fecha($g['fin']) ?></td>
                            <td><?= estadoGarantia($g['fin']) ?></td>
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
                    <?php foreach ($materiales as $m): ?>
                        <tr>
                            <td><?= e($m['nombre']) ?></td>
                            <td><?= $m['cantidad'] ?> <?= e($m['unidad']) ?></td>
                            <td><?= $m['minimo'] ?> <?= e($m['unidad']) ?></td>
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
