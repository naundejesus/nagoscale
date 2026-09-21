<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: apartamentos.php');
    exit;
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT t.apartamento_id FROM temporadas_precio t
     JOIN apartamentos a ON a.id = t.apartamento_id
     WHERE t.id = ? AND a.user_id = ?'
);
$stmt->execute([$id, current_user_id()]);
$apartamentoId = $stmt->fetchColumn();

if ($apartamentoId) {
    $stmt = $pdo->prepare('DELETE FROM temporadas_precio WHERE id = ?');
    $stmt->execute([$id]);
    header('Location: apartamento_form.php?id=' . (int) $apartamentoId);
    exit;
}

header('Location: apartamentos.php');
exit;
