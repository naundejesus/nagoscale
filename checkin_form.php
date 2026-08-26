<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$reservaId = (int) ($_GET['reserva_id'] ?? ($_POST['reserva_id'] ?? 0));

$stmt = $pdo->prepare(
    'SELECT r.*, a.nombre AS apartamento_nombre
     FROM reservas r
     JOIN apartamentos a ON a.id = r.apartamento_id
     WHERE r.id = ? AND a.user_id = ?'
);
$stmt->execute([$reservaId, current_user_id()]);
$reserva = $stmt->fetch();
if (!$reserva) {
    http_response_code(404);
    die('Reserva no encontrada.');
}

$error = '';
$uploadDir = __DIR__ . '/assets/uploads/checkin/';
$mimeExtensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
    $tipoDocumento = trim($_POST['tipo_documento'] ?? '');
    $numeroDocumento = trim($_POST['numero_documento'] ?? '');
    $nacionalidad = trim($_POST['nacionalidad'] ?? '');
    $procedencia = trim($_POST['procedencia'] ?? '');
    $horaLlegada = trim($_POST['hora_llegada'] ?? '');
    $placaVehiculo = trim($_POST['placa_vehiculo'] ?? '');

    if ($nombreCompleto === '') {
        $error = 'El nombre completo es obligatorio.';
    } else {
        $fotoDocumento = null;
        if (!empty($_FILES['foto_documento']['tmp_name']) && $_FILES['foto_documento']['error'] === UPLOAD_ERR_OK) {
            $mime = mime_content_type($_FILES['foto_documento']['tmp_name']);
            if (isset($mimeExtensiones[$mime])) {
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $archivo = bin2hex(random_bytes(16)) . '.' . $mimeExtensiones[$mime];
                if (move_uploaded_file($_FILES['foto_documento']['tmp_name'], $uploadDir . $archivo)) {
                    $fotoDocumento = $archivo;
                }
            }
        }

        $stmt = $pdo->prepare(
            'INSERT INTO checkin_huespedes
                (reserva_id, nombre_completo, tipo_documento, numero_documento, nacionalidad, procedencia, hora_llegada, placa_vehiculo, foto_documento)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $reservaId,
            $nombreCompleto,
            $tipoDocumento ?: null,
            $numeroDocumento ?: null,
            $nacionalidad ?: null,
            $procedencia ?: null,
            $horaLlegada ?: null,
            $placaVehiculo ?: null,
            $fotoDocumento,
        ]);

        header('Location: checkin_form.php?reserva_id=' . $reservaId . '&guardado=1');
        exit;
    }
}

$stmt = $pdo->prepare('SELECT * FROM checkin_huespedes WHERE reserva_id = ? ORDER BY created_at ASC');
$stmt->execute([$reservaId]);
$huespedes = $stmt->fetchAll();

$pageTitle = 'Check-in — ' . $reserva['apartamento_nombre'];
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <h1>Check-in de huéspedes</h1>
  <a href="checkin.php" class="btn-link">← Volver</a>
</div>

<div class="form-card" style="margin-bottom:16px;">
  <p style="margin:0;"><strong><?= e($reserva['apartamento_nombre']) ?></strong></p>
  <p style="margin:4px 0 0; font-size:13px; color:var(--text-muted);">
    <?= e((new DateTime($reserva['fecha_inicio']))->format('d/m/Y')) ?> — <?= e((new DateTime($reserva['fecha_fin']))->format('d/m/Y')) ?>
  </p>
</div>

<?php if (isset($_GET['guardado'])): ?>
  <p class="alert alert-success">Huésped registrado correctamente.</p>
<?php endif; ?>
<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>

<div class="table-wrap" style="margin-bottom:20px;">
<table class="data-table">
  <thead>
    <tr>
      <th>Nombre</th>
      <th>Documento</th>
      <th>Nacionalidad</th>
      <th>Procedencia</th>
      <th>Llegada</th>
      <th>Placa</th>
      <th>Documento (foto)</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$huespedes): ?>
      <tr><td colspan="8" class="empty-state">Aún no hay huéspedes registrados para esta reserva.</td></tr>
    <?php endif; ?>
    <?php foreach ($huespedes as $h): ?>
      <tr>
        <td><?= e($h['nombre_completo']) ?></td>
        <td><?= e(trim(($h['tipo_documento'] ?? '') . ' ' . ($h['numero_documento'] ?? ''))) ?: '—' ?></td>
        <td><?= e($h['nacionalidad'] ?? '') ?: '—' ?></td>
        <td><?= e($h['procedencia'] ?? '') ?: '—' ?></td>
        <td><?= e($h['hora_llegada'] ?? '') ?: '—' ?></td>
        <td><?= e($h['placa_vehiculo'] ?? '') ?: '—' ?></td>
        <td>
          <?php if ($h['foto_documento']): ?>
            <a href="checkin_foto.php?id=<?= (int) $h['id'] ?>" target="_blank" class="btn-link">Ver foto</a>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
        <td class="actions-cell">
          <form method="post" action="checkin_huesped_delete.php" onsubmit="return confirm('¿Eliminar este huésped?');" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $h['id'] ?>">
            <input type="hidden" name="reserva_id" value="<?= (int) $reservaId ?>">
            <button type="submit" class="btn-link btn-link-danger">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<form class="form-card" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <input type="hidden" name="reserva_id" value="<?= (int) $reservaId ?>">
  <h2 style="margin:0 0 4px; font-size:16px;">Registrar nuevo huésped</h2>

  <label>Nombre completo
    <input type="text" name="nombre_completo" required>
  </label>

  <div class="form-grid-2">
    <label>Tipo de documento
      <select name="tipo_documento">
        <option value="">Selecciona...</option>
        <option value="Cédula de ciudadanía">Cédula de ciudadanía</option>
        <option value="Cédula de extranjería">Cédula de extranjería</option>
        <option value="Pasaporte">Pasaporte</option>
        <option value="Otro">Otro</option>
      </select>
    </label>
    <label>Número de documento
      <input type="text" name="numero_documento">
    </label>
  </div>

  <div class="form-grid-2">
    <label>Nacionalidad
      <input type="text" name="nacionalidad" placeholder="Colombia">
    </label>
    <label>Procedencia
      <input type="text" name="procedencia" placeholder="Ciudad de origen">
    </label>
  </div>

  <div class="form-grid-2">
    <label>Hora estimada de llegada
      <input type="time" name="hora_llegada">
    </label>
    <label>Placa del vehículo (si aplica)
      <input type="text" name="placa_vehiculo">
    </label>
  </div>

  <label>Foto del documento (opcional)
    <input type="file" name="foto_documento" accept="image/png,image/jpeg,image/webp">
  </label>

  <button type="submit" class="btn btn-primary">Registrar huésped</button>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
