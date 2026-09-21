<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reservas.php');
    exit;
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare(
        'DELETE r FROM reservas r
         JOIN apartamentos a ON a.id = r.apartamento_id
         WHERE r.id = ? AND a.user_id = ?'
    );
    $stmt->execute([$id, current_user_id()]);
}

header('Location: reservas.php?eliminado=1');
exit;
