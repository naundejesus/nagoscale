<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$stmt = $pdo->prepare(
    'SELECT s.*, a.nombre AS apartamento_nombre
     FROM solicitudes s
     JOIN apartamentos a ON a.id = s.apartamento_id
     WHERE a.user_id = ?
     ORDER BY (s.estado = "pendiente") DESC, s.created_at DESC'
);
$stmt->execute([current_user_id()]);
$solicitudes = $stmt->fetchAll();

$pageTitle = 'Solicitudes';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <h1>Solicitudes de clientes</h1>
</div>

<?php if (isset($_GET['aprobada'])): ?>
  <p class="alert alert-success">Solicitud aprobada y convertida en reserva.</p>
<?php endif; ?>
<?php if (isset($_GET['rechazada'])): ?>
  <p class="alert alert-success">Solicitud rechazada.</p>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
  <p class="alert alert-error"><?= e($_GET['error']) ?></p>
<?php endif; ?>

<div class="table-wrap">
<table class="data-table">
  <thead>
    <tr>
      <th>Estado</th>
      <th>Alojamiento</th>
      <th>Cliente</th>
      <th>Contacto</th>
      <th>Fechas solicitadas</th>
      <th>Valor estimado</th>
      <th>Mensaje</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$solicitudes): ?>
      <tr><td colspan="8" class="empty-state">Aún no has recibido solicitudes de clientes.</td></tr>
    <?php endif; ?>
    <?php foreach ($solicitudes as $s): ?>
      <tr>
        <td><span class="badge badge-estado-<?= e($s['estado']) ?>"><?= e(ucfirst($s['estado'])) ?></span></td>
        <td><?= e($s['apartamento_nombre']) ?></td>
        <td><?= e($s['nombre_cliente']) ?></td>
        <td>
          <?= e($s['telefono']) ?>
          <?php if ($s['correo']): ?><br><?= e($s['correo']) ?><?php endif; ?>
        </td>
        <td>
          <?= e((new DateTime($s['fecha_inicio']))->format('d/m/Y')) ?>
          &ndash;
          <?= e((new DateTime($s['fecha_fin']))->format('d/m/Y')) ?>
        </td>
        <td><?= $s['valor_estimado'] !== null ? formatCOP($s['valor_estimado']) : '—' ?></td>
        <td><?= e($s['mensaje'] ?? '') ?></td>
        <td class="actions-cell">
          <?php if ($s['estado'] === 'pendiente'): ?>
            <a href="solicitud_aprobar.php?id=<?= (int) $s['id'] ?>" class="btn-link">Aprobar</a>
            <form method="post" action="solicitud_rechazar.php" onsubmit="return confirm('¿Rechazar esta solicitud?');" class="inline-form">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
              <button type="submit" class="btn-link btn-link-danger">Rechazar</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
