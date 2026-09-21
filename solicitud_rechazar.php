<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: solicitudes.php');
    exit;
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare(
        "UPDATE solicitudes SET estado = 'rechazada'
         WHERE id = ? AND estado = 'pendiente'
           AND apartamento_id IN (SELECT id FROM apartamentos WHERE user_id = ?)"
    );
    $stmt->execute([$id, current_user_id()]);
}

header('Location: solicitudes.php?rechazada=1');
exit;
