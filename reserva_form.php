<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$id = null;
if (isset($_GET['id']) && ctype_digit((string) $_GET['id'])) {
    $id = (int) $_GET['id'];
} elseif (isset($_POST['id']) && ctype_digit((string) $_POST['id'])) {
    $id = (int) $_POST['id'];
}

$reserva = [
    'apartamento_id' => '',
    'fecha_inicio' => date('Y-m-d'),
    'fecha_fin' => date('Y-m-d'),
    'plataforma' => 'airbnb',
    'valor_total' => '',
    'notas' => '',
];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM reservas WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        http_response_code(404);
        die('Reserva no encontrada.');
    }
    $reserva = $found;
}

$error = '';
$plataformasValidas = ['airbnb', 'booking', 'web'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $apartamentoId = (int) ($_POST['apartamento_id'] ?? 0);
    $fechaInicio = $_POST['fecha_inicio'] ?? '';
    $fechaFin = $_POST['fecha_fin'] ?? '';
    $plataforma = $_POST['plataforma'] ?? '';
    $valorTotal = (float) ($_POST['valor_total'] ?? 0);
    $notas = trim((string) ($_POST['notas'] ?? ''));

    if ($apartamentoId <= 0) {
        $error = 'Selecciona un apartamento.';
    } elseif (DateTime::createFromFormat('Y-m-d', $fechaInicio) === false) {
        $error = 'La fecha de inicio no es válida.';
    } elseif (DateTime::createFromFormat('Y-m-d', $fechaFin) === false) {
        $error = 'La fecha de fin no es válida.';
    } elseif ($fechaFin < $fechaInicio) {
        $error = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
    } elseif (!in_array($plataforma, $plataformasValidas, true)) {
        $error = 'Selecciona una plataforma válida.';
    } elseif ($valorTotal <= 0) {
        $error = 'El valor total debe ser mayor que cero.';
    } else {
        $sqlSolape = 'SELECT COUNT(*) FROM reservas WHERE apartamento_id = ? AND fecha_inicio <= ? AND fecha_fin >= ?';
        $paramsSolape = [$apartamentoId, $fechaFin, $fechaInicio];
        if ($id) {
            $sqlSolape .= ' AND id != ?';
            $paramsSolape[] = $id;
        }
        $stmt = $pdo->prepare($sqlSolape);
        $stmt->execute($paramsSolape);
        if ((int) $stmt->fetchColumn() > 0) {
            $error = 'Ese apartamento ya tiene una reserva que se cruza con las fechas seleccionadas.';
        }
    }

    if (!$error) {
        $valorPropietario = round($valorTotal * 0.75, 2);
        $valorComision = round($valorTotal - $valorPropietario, 2);

        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE reservas
                 SET apartamento_id = ?, fecha_inicio = ?, fecha_fin = ?, plataforma = ?, valor_total = ?,
                     valor_propietario = ?, valor_comision = ?, notas = ?
                 WHERE id = ?'
            );
            $stmt->execute([$apartamentoId, $fechaInicio, $fechaFin, $plataforma, $valorTotal, $valorPropietario, $valorComision, $notas ?: null, $id]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO reservas (apartamento_id, fecha_inicio, fecha_fin, plataforma, valor_total, valor_propietario, valor_comision, notas)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$apartamentoId, $fechaInicio, $fechaFin, $plataforma, $valorTotal, $valorPropietario, $valorComision, $notas ?: null]);
        }
        header('Location: reservas.php?guardado=1');
        exit;
    }

    $reserva = [
        'apartamento_id' => $apartamentoId,
        'fecha_inicio' => $fechaInicio,
        'fecha_fin' => $fechaFin,
        'plataforma' => $plataforma,
        'valor_total' => $valorTotal,
        'notas' => $notas,
    ];
}

$apartamentos = $pdo->query('SELECT id, nombre FROM apartamentos ORDER BY nombre')->fetchAll();

$pageTitle = $id ? 'Editar reserva' : 'Nueva reserva';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <h1><?= $id ? 'Editar reserva' : 'Nueva reserva' ?></h1>
  <a href="reservas.php" class="btn-link">&larr; Volver a reservas</a>
</div>

<?php if (!$apartamentos): ?>
  <p class="alert alert-error">
    Primero debes <a href="apartamento_form.php">crear al menos un apartamento</a> antes de registrar reservas.
  </p>
<?php else: ?>

<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>

<form class="form-card" method="post">
  <?= csrf_field() ?>
  <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

  <label>Apartamento
    <select name="apartamento_id" required>
      <option value="">Selecciona...</option>
      <?php foreach ($apartamentos as $a): ?>
        <option value="<?= (int) $a['id'] ?>" <?= (string) $reserva['apartamento_id'] === (string) $a['id'] ? 'selected' : '' ?>>
          <?= e($a['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>

  <label>Fecha de inicio (check-in)
    <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?= e($reserva['fecha_inicio']) ?>" required>
  </label>

  <label>Fecha de fin (check-out)
    <input type="date" name="fecha_fin" id="fecha_fin" value="<?= e($reserva['fecha_fin']) ?>" required>
  </label>

  <div class="calendario-wrap">
    <span class="calendario-titulo">Disponibilidad de este apartamento</span>
    <div id="calendario" data-excluir-id="<?= $id ? (int) $id : '' ?>"></div>
  </div>

  <label>Plataforma
    <select name="plataforma" required>
      <option value="airbnb" <?= $reserva['plataforma'] === 'airbnb' ? 'selected' : '' ?>>Airbnb</option>
      <option value="booking" <?= $reserva['plataforma'] === 'booking' ? 'selected' : '' ?>>Booking</option>
      <option value="web" <?= $reserva['plataforma'] === 'web' ? 'selected' : '' ?>>Publicidad web</option>
    </select>
  </label>

  <label>Valor total de la reserva (COP)
    <input type="number" name="valor_total" id="valor_total" min="0" step="1" value="<?= e($reserva['valor_total']) ?>" required>
  </label>

  <div class="split-preview">
    <div>75% Propietario <strong id="preview_propietario">$ 0</strong></div>
    <div>25% NagoScale <strong id="preview_comision">$ 0</strong></div>
  </div>

  <label>Notas (opcional)
    <input type="text" name="notas" maxlength="255" value="<?= e($reserva['notas'] ?? '') ?>" placeholder="Referencia, huésped, etc.">
  </label>

  <button type="submit" class="btn btn-primary">Guardar reserva</button>
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

  document.getElementById('fecha_inicio').addEventListener('change', function () {
    var fin = document.getElementById('fecha_fin');
    if (fin.value < this.value) {
      fin.value = this.value;
    }
    fin.min = this.value;
  });
</script>
<script src="assets/js/calendario.js"></script>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
