<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$filtro = obtener_reservas_filtradas($pdo);
$desde = $filtro['desde'];
$hasta = $filtro['hasta'];
$reservas = $filtro['reservas'];
$totales = $filtro['totales'];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reporte de reservas · <?= e(brand_name()) ?></title>
<link rel="icon" type="image/png" href="assets/img/favicon.png">
<link rel="stylesheet" href="assets/css/style.css?v=<?= e(asset_version()) ?>">
<?php require __DIR__ . '/includes/brand_style.php'; ?>
<style>
  body { background: #fff; }
  .print-actions { margin: 20px; }
  .print-header { padding: 20px; }
  .print-header h1 { margin: 0 0 4px; font-size: 20px; }
  .print-header p { margin: 0; color: var(--text-muted); font-size: 13px; }
  @media print {
    .print-actions { display: none; }
  }
</style>
</head>
<body>

<div class="print-actions">
  <button onclick="window.print()" class="btn btn-primary">Imprimir / Guardar como PDF</button>
</div>

<div class="print-header">
  <h1>Reporte de reservas — <?= e(brand_name()) ?></h1>
  <p>
    Generado el <?= e((new DateTime())->format('d/m/Y H:i')) ?>
    <?php if ($desde || $hasta): ?>
      · Periodo: <?= e($desde ?: 'inicio') ?> a <?= e($hasta ?: 'hoy') ?>
    <?php endif; ?>
  </p>
</div>

<div class="container" style="padding-top:0;">
  <div class="summary-cards">
    <div class="summary-card">
      <span class="summary-label">Reservas</span>
      <span class="summary-value"><?= count($reservas) ?></span>
    </div>
    <div class="summary-card">
      <span class="summary-label">Valor total</span>
      <span class="summary-value"><?= formatCOP($totales['total']) ?></span>
    </div>
    <?php if (mostrar_split_comision()): ?>
      <div class="summary-card summary-card-owner">
        <span class="summary-label">75% Propietarios</span>
        <span class="summary-value"><?= formatCOP($totales['propietario']) ?></span>
      </div>
      <div class="summary-card summary-card-commission">
        <span class="summary-label">25% <?= e(brand_name()) ?></span>
        <span class="summary-value"><?= formatCOP($totales['comision']) ?></span>
      </div>
    <?php endif; ?>
  </div>

  <div class="table-wrap">
  <table class="data-table">
    <thead>
      <tr>
        <th>Check-in</th>
        <th>Check-out</th>
        <th>Alojamiento</th>
        <th>Propietario</th>
        <th>Plataforma</th>
        <th>Valor total</th>
        <?php if (mostrar_split_comision()): ?>
          <th>75% Propietario</th>
          <th>25% <?= e(brand_name()) ?></th>
        <?php endif; ?>
        <th>Notas</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$reservas): ?>
        <tr><td colspan="<?= mostrar_split_comision() ? 9 : 7 ?>" class="empty-state">No hay reservas para los filtros seleccionados.</td></tr>
      <?php endif; ?>
      <?php foreach ($reservas as $r): ?>
        <tr>
          <td><?= e((new DateTime($r['fecha_inicio']))->format('d/m/Y')) ?></td>
          <td><?= e((new DateTime($r['fecha_fin']))->format('d/m/Y')) ?></td>
          <td><?= e($r['apartamento_nombre']) ?></td>
          <td><?= e($r['apartamento_propietario'] ?? '') ?></td>
          <td><?= e(plataformaLabel($r['plataforma'])) ?></td>
          <td><?= formatCOP($r['valor_total']) ?></td>
          <?php if (mostrar_split_comision()): ?>
            <td><?= formatCOP($r['valor_propietario']) ?></td>
            <td><?= formatCOP($r['valor_comision']) ?></td>
          <?php endif; ?>
          <td><?= e($r['notas'] ?? '') ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

</body>
</html>
