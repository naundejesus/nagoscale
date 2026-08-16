<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$id = (int) ($_GET['id'] ?? ($_POST['id'] ?? 0));

$stmt = $pdo->prepare(
    'SELECT s.*, a.nombre AS apartamento_nombre
     FROM solicitudes s
     JOIN apartamentos a ON a.id = s.apartamento_id
     WHERE s.id = ? AND a.user_id = ?'
);
$stmt->execute([$id, current_user_id()]);
$solicitud = $stmt->fetch();

if (!$solicitud) {
    http_response_code(404);
    die('Solicitud no encontrada.');
}

if ($solicitud['estado'] !== 'pendiente') {
    header('Location: solicitudes.php');
    exit;
}

$error = '';
$plataformasValidas = ['airbnb', 'booking', 'web'];
$valorTotal = $solicitud['valor_estimado'] ?? '';
$plataforma = 'web';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $valorTotal = (float) ($_POST['valor_total'] ?? 0);
    $plataforma = $_POST['plataforma'] ?? '';

    if ($valorTotal <= 0) {
        $error = 'El valor total debe ser mayor que cero.';
    } elseif (!in_array($plataforma, $plataformasValidas, true)) {
        $error = 'Selecciona una plataforma válida.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM reservas WHERE apartamento_id = ? AND fecha_inicio <= ? AND fecha_fin >= ?'
        );
        $stmt->execute([$solicitud['apartamento_id'], $solicitud['fecha_fin'], $solicitud['fecha_inicio']]);
        if ((int) $stmt->fetchColumn() > 0) {
            $error = 'Ya existe una reserva confirmada que se cruza con estas fechas. No se puede aprobar.';
        }
    }

    if (!$error) {
        $valorPropietario = round($valorTotal * 0.75, 2);
        $valorComision = round($valorTotal - $valorPropietario, 2);
        $notas = 'Cliente: ' . $solicitud['nombre_cliente'] . ', Tel: ' . $solicitud['telefono'];
        if ($solicitud['correo']) {
            $notas .= ', ' . $solicitud['correo'];
        }

        $stmt = $pdo->prepare(
            'INSERT INTO reservas (apartamento_id, fecha_inicio, fecha_fin, plataforma, valor_total, valor_propietario, valor_comision, notas)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $solicitud['apartamento_id'],
            $solicitud['fecha_inicio'],
            $solicitud['fecha_fin'],
            $plataforma,
            $valorTotal,
            $valorPropietario,
            $valorComision,
            $notas,
        ]);
        $reservaId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare("UPDATE solicitudes SET estado = 'aprobada', reserva_id = ? WHERE id = ?");
        $stmt->execute([$reservaId, $id]);

        header('Location: solicitudes.php?aprobada=1');
        exit;
    }
}

$pageTitle = 'Aprobar solicitud';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <h1>Aprobar solicitud</h1>
  <a href="solicitudes.php" class="btn-link">&larr; Volver a solicitudes</a>
</div>

<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>

<div class="form-card">
  <p><strong>Apartamento:</strong> <?= e($solicitud['apartamento_nombre']) ?></p>
  <p><strong>Cliente:</strong> <?= e($solicitud['nombre_cliente']) ?> — <?= e($solicitud['telefono']) ?></p>
  <?php if ($solicitud['correo']): ?><p><strong>Correo:</strong> <?= e($solicitud['correo']) ?></p><?php endif; ?>
  <p>
    <strong>Fechas:</strong>
    <?= e((new DateTime($solicitud['fecha_inicio']))->format('d/m/Y')) ?>
    &ndash;
    <?= e((new DateTime($solicitud['fecha_fin']))->format('d/m/Y')) ?>
  </p>
  <?php if ($solicitud['mensaje']): ?><p><strong>Mensaje:</strong> <?= e($solicitud['mensaje']) ?></p><?php endif; ?>
</div>

<form class="form-card" method="post" style="margin-top:16px;">
  <?= csrf_field() ?>

  <label>Plataforma
    <select name="plataforma" required>
      <option value="web" <?= $plataforma === 'web' ? 'selected' : '' ?>>Publicidad web</option>
      <option value="airbnb" <?= $plataforma === 'airbnb' ? 'selected' : '' ?>>Airbnb</option>
      <option value="booking" <?= $plataforma === 'booking' ? 'selected' : '' ?>>Booking</option>
    </select>
  </label>

  <label>Valor total de la reserva (COP)
    <input type="number" name="valor_total" id="valor_total" min="0" step="1" value="<?= e($valorTotal) ?>" required>
  </label>

  <div class="split-preview">
    <div>75% Propietario <strong id="preview_propietario">$ 0</strong></div>
    <div>25% <?= e(brand_name()) ?> <strong id="preview_comision">$ 0</strong></div>
  </div>

  <button type="submit" class="btn btn-primary">Aprobar y crear reserva</button>
</form>

<script>
  function formatCOP(n) {
    return '$ ' + Math.round(n).toLocaleString('es-CO');
  }
  function updatePreview() {
    var total = parseFloat(document.getElementById('valor_total').value) || 0;
    var propietario = Math.round(total * 0.75);
    var comision = Math.round(total - propietario);
    document.getElementById('preview_propietario').textContent = formatCOP(propietario);
    document.getElementById('preview_comision').textContent = formatCOP(comision);
  }
  document.getElementById('valor_total').addEventListener('input', updatePreview);
  updatePreview();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
