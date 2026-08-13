<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$apartamentos = $pdo->query('SELECT id, nombre FROM apartamentos ORDER BY nombre')->fetchAll();

$filtro = obtener_reservas_filtradas($pdo);
$desde = $filtro['desde'];
$hasta = $filtro['hasta'];
$apartamentoId = $filtro['apartamento_id'];
$plataforma = $filtro['plataforma'];
$reservas = $filtro['reservas'];
$totales = $filtro['totales'];

$queryString = http_build_query(array_filter([
    'desde' => $desde,
    'hasta' => $hasta,
    'apartamento_id' => $apartamentoId,
    'plataforma' => $plataforma,
]));

$pageTitle = 'Reservas';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <h1>Reservas</h1>
  <a href="reserva_form.php" class="btn btn-primary">+ Nueva reserva</a>
</div>

<?php if (isset($_GET['guardado'])): ?>
  <p class="alert alert-success">Reserva guardada correctamente.</p>
<?php endif; ?>
<?php if (isset($_GET['eliminado'])): ?>
  <p class="alert alert-success">Reserva eliminada.</p>
<?php endif; ?>

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
  <label>Plataforma
    <select name="plataforma">
      <option value="">Todas</option>
      <option value="airbnb" <?= $plataforma === 'airbnb' ? 'selected' : '' ?>>Airbnb</option>
      <option value="booking" <?= $plataforma === 'booking' ? 'selected' : '' ?>>Booking</option>
      <option value="web" <?= $plataforma === 'web' ? 'selected' : '' ?>>Publicidad web</option>
    </select>
  </label>
  <button type="submit" class="btn btn-secondary">Filtrar</button>
  <?php if ($desde || $hasta || $apartamentoId || $plataforma): ?>
    <a href="reservas.php" class="btn-link">Limpiar</a>
  <?php endif; ?>
</form>

<div class="export-bar">
  <a href="reservas_exportar.php?<?= e($queryString) ?>" class="btn btn-secondary">⬇ Exportar a Excel</a>
  <a href="reservas_imprimir.php?<?= e($queryString) ?>" class="btn btn-secondary" target="_blank">🖨 Exportar a PDF</a>
</div>

<div class="summary-cards">
  <div class="summary-card">
    <span class="summary-label">Reservas</span>
    <span class="summary-value"><?= count($reservas) ?></span>
  </div>
  <div class="summary-card">
    <span class="summary-label">Valor total</span>
    <span class="summary-value"><?= formatCOP($totales['total']) ?></span>
  </div>
  <div class="summary-card summary-card-owner">
    <span class="summary-label">75% Propietarios</span>
    <span class="summary-value"><?= formatCOP($totales['propietario']) ?></span>
  </div>
  <div class="summary-card summary-card-commission">
    <span class="summary-label">25% NagoScale</span>
    <span class="summary-value"><?= formatCOP($totales['comision']) ?></span>
  </div>
</div>

<div class="table-wrap">
<table class="data-table">
  <thead>
    <tr>
      <th>Check-in</th>
      <th>Check-out</th>
      <th>Apartamento</th>
      <th>Propietario</th>
      <th>Plataforma</th>
      <th>Valor total</th>
      <th>75% Propietario</th>
      <th>25% NagoScale</th>
      <th>Notas</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$reservas): ?>
      <tr><td colspan="10" class="empty-state">No hay reservas para los filtros seleccionados.</td></tr>
    <?php endif; ?>
    <?php foreach ($reservas as $r): ?>
      <tr>
        <td><?= e((new DateTime($r['fecha_inicio']))->format('d/m/Y')) ?></td>
        <td><?= e((new DateTime($r['fecha_fin']))->format('d/m/Y')) ?></td>
        <td><?= e($r['apartamento_nombre']) ?></td>
        <td><?= e($r['apartamento_propietario'] ?? '') ?></td>
        <td><span class="badge badge-<?= e($r['plataforma']) ?>"><?= e(plataformaLabel($r['plataforma'])) ?></span></td>
        <td><?= formatCOP($r['valor_total']) ?></td>
        <td><?= formatCOP($r['valor_propietario']) ?></td>
        <td><?= formatCOP($r['valor_comision']) ?></td>
        <td><?= e($r['notas'] ?? '') ?></td>
        <td class="actions-cell">
          <a href="reserva_form.php?id=<?= (int) $r['id'] ?>" class="btn-link">Editar</a>
          <form method="post" action="reserva_delete.php" onsubmit="return confirm('¿Eliminar esta reserva?');" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button type="submit" class="btn-link btn-link-danger">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
