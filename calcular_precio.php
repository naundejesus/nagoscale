<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

$apartamentoId = (int) ($_GET['apartamento_id'] ?? 0);
$fechaInicio = $_GET['fecha_inicio'] ?? '';
$fechaFin = $_GET['fecha_fin'] ?? '';

$respuestaVacia = ['tiene_precio' => false, 'valor_total' => null, 'valor_50' => null];

if ($apartamentoId <= 0
    || DateTime::createFromFormat('Y-m-d', $fechaInicio) === false
    || DateTime::createFromFormat('Y-m-d', $fechaFin) === false
    || $fechaFin <= $fechaInicio
) {
    echo json_encode($respuestaVacia);
    exit;
}

$stmt = $pdo->prepare('SELECT precio_noche, precio_fin_semana FROM apartamentos WHERE id = ?');
$stmt->execute([$apartamentoId]);
$apartamento = $stmt->fetch();

if (!$apartamento) {
    echo json_encode($respuestaVacia);
    exit;
}

$valorTotal = calcular_valor_estadia(
    $apartamento['precio_noche'] !== null ? (float) $apartamento['precio_noche'] : null,
    $apartamento['precio_fin_semana'] !== null ? (float) $apartamento['precio_fin_semana'] : null,
    $fechaInicio,
    $fechaFin
);

if ($valorTotal === null) {
    echo json_encode($respuestaVacia);
    exit;
}

echo json_encode([
    'tiene_precio' => true,
    'valor_total' => $valorTotal,
    'valor_50' => round($valorTotal * 0.5, 2),
]);
exit;
