<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

// Pública a propósito: la usa tanto el panel interno como el formulario
// público de solicitud de reserva. Solo expone qué fechas están ocupadas
// para un apartamento (sin datos sensibles), es necesaria para que un
// cliente sin cuenta pueda ver la disponibilidad antes de solicitar.

header('Content-Type: application/json; charset=UTF-8');

$apartamentoId = (int) ($_GET['apartamento_id'] ?? 0);
$excluirId = (int) ($_GET['excluir_id'] ?? 0);

if ($apartamentoId <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare('SELECT COUNT(*) FROM apartamentos WHERE id = ?');
$stmt->execute([$apartamentoId]);
if ((int) $stmt->fetchColumn() === 0) {
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
    // El día de checkout (fecha_fin) NO se marca como ocupado: el huésped
    // sale antes del mediodía y ese mismo día puede empezar otra reserva.
    $periodo = new DatePeriod($inicio, new DateInterval('P1D'), $fin);
    foreach ($periodo as $dia) {
        $fechasOcupadas[] = $dia->format('Y-m-d');
    }
}

echo json_encode(array_values(array_unique($fechasOcupadas)));
exit;
