<?php
// DATOS DE EJEMPLO. Cuando exista la base de datos, estos arreglos se llenan con consultas.
// Las fechas son relativas a hoy para que se vean todos los estados.
$dia = fn(int $n) => (new DateTimeImmutable('today'))->modify("$n days");

$cotizaciones = [
    ['cliente' => 'Cliente de ejemplo A', 'trabajo' => 'Portón corredizo de 4 m',      'solicitado' => $dia(-1)],
    ['cliente' => 'Cliente de ejemplo B', 'trabajo' => 'Barandal para escalera',       'solicitado' => $dia(-3)],
    ['cliente' => 'Cliente de ejemplo C', 'trabajo' => 'Protecciones para 3 ventanas', 'solicitado' => $dia(-6)],
];

$entregas = [
    ['cliente' => 'Cliente de ejemplo D', 'trabajo' => 'Escalera de caracol', 'entrega' => $dia(-2)],
    ['cliente' => 'Cliente de ejemplo E', 'trabajo' => 'Reja perimetral',     'entrega' => $dia(4)],
    ['cliente' => 'Cliente de ejemplo F', 'trabajo' => 'Estructura de techo', 'entrega' => $dia(20)],
];

$garantias = [
    ['cliente' => 'Cliente de ejemplo G', 'trabajo' => 'Puerta principal',  'entregado' => $dia(-380), 'fin' => $dia(-15)],
    ['cliente' => 'Cliente de ejemplo H', 'trabajo' => 'Portón abatible',   'entregado' => $dia(-350), 'fin' => $dia(15)],
    ['cliente' => 'Cliente de ejemplo I', 'trabajo' => 'Barandal de balcón', 'entregado' => $dia(-60),  'fin' => $dia(305)],
];

$materiales = [
    ['nombre' => 'PTR 2 x 2 pulgadas',  'cantidad' => 18, 'minimo' => 10, 'unidad' => 'tramos'],
    ['nombre' => 'Solera 1 pulgada',    'cantidad' => 6,  'minimo' => 8,  'unidad' => 'tramos'],
    ['nombre' => 'Lámina calibre 18',   'cantidad' => 12, 'minimo' => 5,  'unidad' => 'hojas'],
    ['nombre' => 'Soldadura 6013',      'cantidad' => 3,  'minimo' => 5,  'unidad' => 'kg'],
    ['nombre' => 'Pintura anticorrosiva', 'cantidad' => 9, 'minimo' => 4, 'unidad' => 'litros'],
];
