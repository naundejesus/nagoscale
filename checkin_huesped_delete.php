<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkin.php');
    exit;
}

csrf_verify();

$id = (int) ($_POST['id'] ?? 0);
$reservaId = (int) ($_POST['reserva_id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare(
        'SELECT ch.id, ch.foto_documento
         FROM checkin_huespedes ch
         JOIN reservas r ON r.id = ch.reserva_id
         JOIN apartamentos a ON a.id = r.apartamento_id
         WHERE ch.id = ? AND a.user_id = ?'
    );
    $stmt->execute([$id, current_user_id()]);
    $huesped = $stmt->fetch();

    if ($huesped) {
        $stmt = $pdo->prepare('DELETE FROM checkin_huespedes WHERE id = ?');
        $stmt->execute([$id]);

        if ($huesped['foto_documento']) {
            $ruta = __DIR__ . '/assets/uploads/checkin/' . $huesped['foto_documento'];
            if (is_file($ruta)) {
                unlink($ruta);
            }
        }
    }
}

header('Location: checkin_form.php?reserva_id=' . $reservaId);
exit;
