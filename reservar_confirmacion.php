<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT s.*, a.nombre AS apartamento_nombre, a.direccion, a.user_id
     FROM solicitudes s
     JOIN apartamentos a ON a.id = s.apartamento_id
     WHERE s.id = ?'
);
$stmt->execute([$id]);
$solicitud = $stmt->fetch();

if (!$solicitud) {
    http_response_code(404);
    die('Solicitud no encontrada.');
}

$stmt = $pdo->prepare('SELECT id, username FROM users WHERE id = ?');
$stmt->execute([$solicitud['user_id']]);
$propietarioCuenta = $stmt->fetch();

$codigo = 'SOL-' . str_pad((string) $solicitud['id'], 6, '0', STR_PAD_LEFT);
$pageTitle = 'Solicitud enviada';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · NagoScale</title>
<link rel="icon" type="image/png" href="assets/img/favicon.png">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="ne-body">
<?php require __DIR__ . '/includes/ne_header.php'; ?>

<div class="container">
  <div class="ne-confirm">
    <div class="ne-confirm-icon">✓</div>
    <h1>¡Solicitud enviada!</h1>
    <p style="color:var(--text-secondary);">
      Tu solicitud está pendiente de confirmación. Nos pondremos en contacto contigo pronto para coordinar el pago y confirmar tu reserva.
    </p>

    <div class="ne-confirm-summary">
      <div class="fila"><span>Código</span><strong><?= e($codigo) ?></strong></div>
      <div class="fila"><span>Alojamiento</span><strong><?= e($solicitud['apartamento_nombre']) ?></strong></div>
      <?php if ($solicitud['direccion']): ?>
        <div class="fila"><span>Dirección</span><strong><?= e($solicitud['direccion']) ?></strong></div>
      <?php endif; ?>
      <div class="fila">
        <span>Fechas</span>
        <strong><?= e((new DateTime($solicitud['fecha_inicio']))->format('d/m/Y')) ?> — <?= e((new DateTime($solicitud['fecha_fin']))->format('d/m/Y')) ?></strong>
      </div>
      <?php if ($solicitud['huespedes']): ?>
        <div class="fila"><span>Huéspedes</span><strong><?= (int) $solicitud['huespedes'] ?></strong></div>
      <?php endif; ?>
      <?php if ($solicitud['valor_estimado'] !== null): ?>
        <div class="fila"><span>Valor estimado</span><strong><?= formatCOP($solicitud['valor_estimado']) ?></strong></div>
      <?php endif; ?>
    </div>

    <div class="ne-confirm-actions">
      <a href="reservar.php?u=<?= (int) $solicitud['user_id'] ?>" class="btn btn-primary">Volver a NagoScale</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/ne_footer.php'; ?>
