<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: apartamentos.php');
    exit;
}

csrf_verify();

$apartamentoId = (int) ($_POST['apartamento_id'] ?? 0);
$nombreCliente = trim((string) ($_POST['nombre_cliente'] ?? ''));
$calificacion = (int) ($_POST['calificacion'] ?? 0);
$comentario = trim((string) ($_POST['comentario'] ?? ''));

$stmt = $pdo->prepare('SELECT COUNT(*) FROM apartamentos WHERE id = ? AND user_id = ?');
$stmt->execute([$apartamentoId, current_user_id()]);
$esPropio = (int) $stmt->fetchColumn() > 0;

if ($esPropio && $nombreCliente !== '' && $calificacion >= 1 && $calificacion <= 5) {
    $stmt = $pdo->prepare('INSERT INTO resenas (apartamento_id, nombre_cliente, calificacion, comentario) VALUES (?, ?, ?, ?)');
    $stmt->execute([$apartamentoId, $nombreCliente, $calificacion, $comentario ?: null]);
}

header('Location: apartamento_form.php?id=' . $apartamentoId);
exit;
