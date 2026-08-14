<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$apartamentoId = (int) ($_GET['apartamento_id'] ?? ($_POST['apartamento_id'] ?? 0));

$stmt = $pdo->prepare('SELECT * FROM apartamentos WHERE id = ?');
$stmt->execute([$apartamentoId]);
$apartamento = $stmt->fetch();
if (!$apartamento) {
    http_response_code(404);
    die('Apartamento no encontrado.');
}

$error = '';
$enviado = false;

$solicitud = [
    'nombre_cliente' => '',
    'telefono' => '',
    'correo' => '',
    'mensaje' => '',
    'fecha_inicio' => '',
    'fecha_fin' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $solicitud['nombre_cliente'] = trim($_POST['nombre_cliente'] ?? '');
    $solicitud['telefono'] = trim($_POST['telefono'] ?? '');
    $solicitud['correo'] = trim($_POST['correo'] ?? '');
    $solicitud['mensaje'] = trim($_POST['mensaje'] ?? '');
    $solicitud['fecha_inicio'] = $_POST['fecha_inicio'] ?? '';
    $solicitud['fecha_fin'] = $_POST['fecha_fin'] ?? '';

    if ($solicitud['nombre_cliente'] === '') {
        $error = 'Escribe tu nombre completo.';
    } elseif ($solicitud['telefono'] === '') {
        $error = 'Escribe un teléfono de contacto.';
    } elseif ($solicitud['correo'] !== '' && !filter_var($solicitud['correo'], FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } elseif (DateTime::createFromFormat('Y-m-d', $solicitud['fecha_inicio']) === false) {
        $error = 'La fecha de inicio no es válida.';
    } elseif (DateTime::createFromFormat('Y-m-d', $solicitud['fecha_fin']) === false) {
        $error = 'La fecha de fin no es válida.';
    } elseif ($solicitud['fecha_fin'] < $solicitud['fecha_inicio']) {
        $error = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM reservas WHERE apartamento_id = ? AND fecha_inicio <= ? AND fecha_fin >= ?'
        );
        $stmt->execute([$apartamentoId, $solicitud['fecha_fin'], $solicitud['fecha_inicio']]);
        if ((int) $stmt->fetchColumn() > 0) {
            $error = 'Esas fechas ya no están disponibles para este apartamento. Elige otras.';
        }
    }

    if (!$error) {
        $stmt = $pdo->prepare(
            'INSERT INTO solicitudes (apartamento_id, nombre_cliente, telefono, correo, mensaje, fecha_inicio, fecha_fin)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $apartamentoId,
            $solicitud['nombre_cliente'],
            $solicitud['telefono'],
            $solicitud['correo'] ?: null,
            $solicitud['mensaje'] ?: null,
            $solicitud['fecha_inicio'],
            $solicitud['fecha_fin'],
        ]);

        $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
        $stmt->execute([$apartamento['user_id']]);
        $emailPropietario = (string) $stmt->fetchColumn();

        enviar_notificacion_solicitud($emailPropietario, [
            'apartamento_nombre' => $apartamento['nombre'],
            'nombre_cliente' => $solicitud['nombre_cliente'],
            'telefono' => $solicitud['telefono'],
            'correo' => $solicitud['correo'],
            'fecha_inicio' => $solicitud['fecha_inicio'],
            'fecha_fin' => $solicitud['fecha_fin'],
            'mensaje' => $solicitud['mensaje'],
        ]);

        $enviado = true;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Solicitar reserva · NagoScale</title>
<link rel="icon" type="image/png" href="assets/img/favicon.png">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/includes/banner.php'; ?>
<main class="container">

<div class="page-head">
  <h1>Solicitar reserva — <?= e($apartamento['nombre']) ?></h1>
  <a href="reservar.php?u=<?= (int) $apartamento['user_id'] ?>" class="btn-link">&larr; Ver todos los apartamentos</a>
</div>

<?php if ($enviado): ?>
  <p class="alert alert-success">
    ¡Solicitud enviada! Nos pondremos en contacto contigo pronto para confirmar tu reserva.
  </p>
<?php else: ?>

<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
<?php if ($apartamento['direccion']): ?><p class="auth-subtitle"><?= e($apartamento['direccion']) ?></p><?php endif; ?>

<form class="form-card" method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="apartamento_id" value="<?= (int) $apartamentoId ?>">

  <label>Fecha de inicio (check-in)
    <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?= e($solicitud['fecha_inicio']) ?>" required>
  </label>

  <label>Fecha de fin (check-out)
    <input type="date" name="fecha_fin" id="fecha_fin" value="<?= e($solicitud['fecha_fin']) ?>" required>
  </label>

  <div class="calendario-wrap">
    <span class="calendario-titulo">Disponibilidad</span>
    <div id="calendario" data-apartamento-id="<?= (int) $apartamentoId ?>"></div>
  </div>

  <label>Nombre completo
    <input type="text" name="nombre_cliente" value="<?= e($solicitud['nombre_cliente']) ?>" required>
  </label>

  <label>Teléfono / WhatsApp
    <input type="tel" name="telefono" value="<?= e($solicitud['telefono']) ?>" required>
  </label>

  <label>Correo electrónico (opcional)
    <input type="email" name="correo" value="<?= e($solicitud['correo']) ?>">
  </label>

  <label>Mensaje (opcional)
    <input type="text" name="mensaje" maxlength="500" value="<?= e($solicitud['mensaje']) ?>" placeholder="Número de huéspedes, preguntas, etc.">
  </label>

  <button type="submit" class="btn btn-primary">Enviar solicitud</button>
</form>

<script src="assets/js/calendario.js"></script>

<?php endif; ?>

</main>
<footer class="site-footer">
  <p>NagoScale · Inmuebles por Días</p>
</footer>
</body>
</html>
