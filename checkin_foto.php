<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT ch.foto_documento
     FROM checkin_huespedes ch
     JOIN reservas r ON r.id = ch.reserva_id
     JOIN apartamentos a ON a.id = r.apartamento_id
     WHERE ch.id = ? AND a.user_id = ?'
);
$stmt->execute([$id, current_user_id()]);
$huesped = $stmt->fetch();

if (!$huesped || !$huesped['foto_documento']) {
    http_response_code(404);
    die('Foto no encontrada.');
}

$ruta = __DIR__ . '/assets/uploads/checkin/' . $huesped['foto_documento'];
if (!is_file($ruta)) {
    http_response_code(404);
    die('Foto no encontrada.');
}

$mime = mime_content_type($ruta) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($ruta));
header('Cache-Control: private, no-store');
readfile($ruta);
