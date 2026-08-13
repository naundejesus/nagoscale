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

if ($id > 0) {
    try {
        $stmt = $pdo->prepare('DELETE FROM apartamentos WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: apartamentos.php?guardado=1');
        exit;
    } catch (PDOException $e) {
        header('Location: apartamentos.php?error=' . urlencode('No se puede eliminar: tiene reservas asociadas. Elimina primero esas reservas.'));
        exit;
    }
}

header('Location: apartamentos.php');
exit;
