<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

header('Content-Type: application/json; charset=UTF-8');

$apartamentoId = (int) ($_GET['apartamento_id'] ?? 0);
$excluirId = (int) ($_GET['excluir_id'] ?? 0);

if ($apartamentoId <= 0) {
    echo json_encode([]);
    exit;
}

$sql = 'SELECT fecha_inicio, fecha_fin FROM reservas WHERE apartamento_id = ?';
$params = [$apartamentoId];
if ($excluirId > 0) {
    $sql .= ' AND id != ?';
    $params[] = $excluirId;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$fechasOcupadas = [];
foreach ($stmt->fetchAll() as $row) {
    $inicio = new DateTime($row['fecha_inicio']);
    $fin = new DateTime($row['fecha_fin']);
    $fin->modify('+1 day');
    $periodo = new DatePeriod($inicio, new DateInterval('P1D'), $fin);
    foreach ($periodo as $dia) {
        $fechasOcupadas[] = $dia->format('Y-m-d');
    }
}

echo json_encode(array_values(array_unique($fechasOcupadas)));
exit;
