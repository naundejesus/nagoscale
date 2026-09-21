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
$nombre = trim((string) ($_POST['nombre'] ?? ''));
$fechaInicio = (string) ($_POST['fecha_inicio'] ?? '');
$fechaFin = (string) ($_POST['fecha_fin'] ?? '');
$precioNoche = (string) ($_POST['precio_noche'] ?? '');

$stmt = $pdo->prepare('SELECT COUNT(*) FROM apartamentos WHERE id = ? AND user_id = ?');
$stmt->execute([$apartamentoId, current_user_id()]);
$esPropio = (int) $stmt->fetchColumn() > 0;

$fechaInicioValida = DateTime::createFromFormat('Y-m-d', $fechaInicio) !== false;
$fechaFinValida = DateTime::createFromFormat('Y-m-d', $fechaFin) !== false;

if ($esPropio && $fechaInicioValida && $fechaFinValida && $fechaFin >= $fechaInicio && is_numeric($precioNoche) && (float) $precioNoche > 0) {
    $stmt = $pdo->prepare('INSERT INTO temporadas_precio (apartamento_id, nombre, fecha_inicio, fecha_fin, precio_noche) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$apartamentoId, $nombre ?: null, $fechaInicio, $fechaFin, $precioNoche]);
}

header('Location: apartamento_form.php?id=' . $apartamentoId);
exit;
