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

$stmt = $pdo->prepare('SELECT archivo FROM apartamento_fotos WHERE apartamento_id = ? ORDER BY id LIMIT 20');
$stmt->execute([$apartamentoId]);
$fotos = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare('SELECT nequi_numero, bancolombia_tipo_cuenta, bancolombia_numero, bancolombia_titular FROM users WHERE id = ?');
$stmt->execute([$apartamento['user_id']]);
$datosPago = $stmt->fetch();

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
        $valorEstimado = calcular_valor_estadia(
            $apartamento['precio_noche'] !== null ? (float) $apartamento['precio_noche'] : null,
            $apartamento['precio_fin_semana'] !== null ? (float) $apartamento['precio_fin_semana'] : null,
            $solicitud['fecha_inicio'],
            $solicitud['fecha_fin']
        );

        $stmt = $pdo->prepare(
            'INSERT INTO solicitudes (apartamento_id, nombre_cliente, telefono, correo, mensaje, fecha_inicio, fecha_fin, valor_estimado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $apartamentoId,
            $solicitud['nombre_cliente'],
            $solicitud['telefono'],
            $solicitud['correo'] ?: null,
            $solicitud['mensaje'] ?: null,
            $solicitud['fecha_inicio'],
            $solicitud['fecha_fin'],
            $valorEstimado,
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

<?php if ($fotos): ?>
  <div class="photo-grid" style="margin-bottom:20px;">
    <?php foreach ($fotos as $archivo): ?>
      <img src="assets/uploads/apartamentos/<?= e($archivo) ?>" alt="Foto del apartamento" style="width:100%; height:120px; object-fit:cover; border-radius:8px;">
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php
  $rasgos = [];
  if ($apartamento['direccion']) $rasgos[] = $apartamento['direccion'];
  if ($apartamento['habitaciones'] !== null) $rasgos[] = $apartamento['habitaciones'] . ' habitación(es)';
  if ($apartamento['cocinas'] !== null) $rasgos[] = $apartamento['cocinas'] . ' cocina(s)';
  if ($apartamento['banos'] !== null) $rasgos[] = $apartamento['banos'] . ' baño(s)';
  if ($apartamento['capacidad_huespedes'] !== null) $rasgos[] = 'hasta ' . $apartamento['capacidad_huespedes'] . ' huéspedes';
?>
<?php if ($rasgos): ?>
  <p class="auth-subtitle"><?= e(implode(' · ', $rasgos)) ?></p>
<?php endif; ?>

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

  <div id="precio-box" class="precio-box" hidden>
    <div class="precio-total">
      <span>Valor total estimado</span>
      <strong id="precio-total-valor">$ 0</strong>
    </div>
    <div class="precio-pago">
      <p>Para apartar estas fechas, abona el <strong>50%</strong>: <strong id="precio-50-valor">$ 0</strong></p>
      <?php if ($datosPago && ($datosPago['nequi_numero'] || $datosPago['bancolombia_numero'])): ?>
        <ul class="precio-cuentas">
          <?php if ($datosPago['nequi_numero']): ?>
            <li><strong>Nequi:</strong> <?= e($datosPago['nequi_numero']) ?></li>
          <?php endif; ?>
          <?php if ($datosPago['bancolombia_numero']): ?>
            <li>
              <strong>Bancolombia</strong>
              (<?= e($datosPago['bancolombia_tipo_cuenta'] ?? '') ?>):
              <?= e($datosPago['bancolombia_numero']) ?>
              <?php if ($datosPago['bancolombia_titular']): ?> — <?= e($datosPago['bancolombia_titular']) ?><?php endif; ?>
            </li>
          <?php endif; ?>
        </ul>
        <p class="cal-hint">Envía tu comprobante de pago junto con esta solicitud para agilizar la confirmación.</p>
      <?php endif; ?>
    </div>
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
<script>
  (function () {
    var inicio = document.getElementById('fecha_inicio');
    var fin = document.getElementById('fecha_fin');
    var caja = document.getElementById('precio-box');
    var totalEl = document.getElementById('precio-total-valor');
    var mitadEl = document.getElementById('precio-50-valor');

    function formatCOP(n) {
      return '$ ' + Math.round(n).toLocaleString('es-CO');
    }

    function actualizarPrecio() {
      if (!inicio.value || !fin.value || fin.value <= inicio.value) {
        caja.hidden = true;
        return;
      }
      var url = 'calcular_precio.php?apartamento_id=<?= (int) $apartamentoId ?>'
        + '&fecha_inicio=' + encodeURIComponent(inicio.value)
        + '&fecha_fin=' + encodeURIComponent(fin.value);
      fetch(url)
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data.tiene_precio) {
            caja.hidden = true;
            return;
          }
          totalEl.textContent = formatCOP(data.valor_total);
          mitadEl.textContent = formatCOP(data.valor_50);
          caja.hidden = false;
        })
        .catch(function () { caja.hidden = true; });
    }

    inicio.addEventListener('change', actualizarPrecio);
    fin.addEventListener('change', actualizarPrecio);
    actualizarPrecio();
  })();
</script>

<?php endif; ?>

</main>
<footer class="site-footer">
  <p>NagoScale · Inmuebles por Días</p>
</footer>
</body>
</html>
