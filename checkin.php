<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$stmt = $pdo->prepare('SELECT id, nombre FROM apartamentos WHERE user_id = ? ORDER BY nombre');
$stmt->execute([current_user_id()]);
$apartamentos = $stmt->fetchAll();

$filtro = obtener_reservas_filtradas($pdo);
$desde = $filtro['desde'];
$hasta = $filtro['hasta'];
$apartamentoId = $filtro['apartamento_id'];
$plataforma = $filtro['plataforma'];
$reservas = $filtro['reservas'];

$reservaIds = array_column($reservas, 'id');
$conteos = [];
if ($reservaIds) {
    $placeholders = implode(',', array_fill(0, count($reservaIds), '?'));
    $stmt = $pdo->prepare("SELECT reserva_id, COUNT(*) AS total FROM checkin_huespedes WHERE reserva_id IN ($placeholders) GROUP BY reserva_id");
    $stmt->execute($reservaIds);
    foreach ($stmt->fetchAll() as $fila) {
        $conteos[(int) $fila['reserva_id']] = (int) $fila['total'];
    }
}

$pageTitle = 'Check-in de huéspedes';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <h1>Check-in de huéspedes</h1>
</div>

<form class="filter-bar" method="get">
  <label>Desde
    <input type="date" name="desde" value="<?= e($desde) ?>">
  </label>
  <label>Hasta
    <input type="date" name="hasta" value="<?= e($hasta) ?>">
  </label>
  <label>Apartamento
    <select name="apartamento_id">
      <option value="">Todos</option>
      <?php foreach ($apartamentos as $a): ?>
        <option value="<?= (int) $a['id'] ?>" <?= (string) $apartamentoId === (string) $a['id'] ? 'selected' : '' ?>>
          <?= e($a['nombre']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </label>
  <button type="submit" class="btn btn-secondary">Filtrar</button>
  <?php if ($desde || $hasta || $apartamentoId || $plataforma): ?>
    <a href="checkin.php" class="btn-link">Limpiar</a>
  <?php endif; ?>
</form>

<div class="table-wrap">
<table class="data-table">
  <thead>
    <tr>
      <th>Check-in</th>
      <th>Check-out</th>
      <th>Apartamento</th>
      <th>Huéspedes registrados</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$reservas): ?>
      <tr><td colspan="5" class="empty-state">No hay reservas para los filtros seleccionados.</td></tr>
    <?php endif; ?>
    <?php foreach ($reservas as $r): ?>
      <tr>
        <td><?= e((new DateTime($r['fecha_inicio']))->format('d/m/Y')) ?></td>
        <td><?= e((new DateTime($r['fecha_fin']))->format('d/m/Y')) ?></td>
        <td><?= e($r['apartamento_nombre']) ?></td>
        <td><?= $conteos[(int) $r['id']] ?? 0 ?></td>
        <td class="actions-cell">
          <a href="checkin_form.php?reserva_id=<?= (int) $r['id'] ?>" class="btn-link">Gestionar check-in</a>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
