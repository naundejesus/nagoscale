<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$stmt = $pdo->prepare(
    'SELECT a.*,
            (SELECT COUNT(*) FROM reservas r WHERE r.apartamento_id = a.id) AS total_reservas,
            (SELECT COALESCE(SUM(r.valor_total), 0) FROM reservas r WHERE r.apartamento_id = a.id) AS total_valor,
            (SELECT archivo FROM apartamento_fotos f WHERE f.apartamento_id = a.id ORDER BY f.id LIMIT 1) AS foto_portada
     FROM apartamentos a
     WHERE a.user_id = ?
     ORDER BY a.nombre'
);
$stmt->execute([current_user_id()]);
$apartamentos = $stmt->fetchAll();

$pageTitle = 'Apartamentos';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <h1>Apartamentos</h1>
  <a href="apartamento_form.php" class="btn btn-primary">+ Nuevo apartamento</a>
</div>

<?php if (isset($_GET['guardado'])): ?>
  <p class="alert alert-success">Apartamento guardado correctamente.</p>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
  <p class="alert alert-error"><?= e($_GET['error']) ?></p>
<?php endif; ?>

<div class="table-wrap">
<table class="data-table">
  <thead>
    <tr>
      <th></th>
      <th>Nombre</th>
      <th>Propietario</th>
      <th>Características</th>
      <th>Precio/noche</th>
      <th>Reservas registradas</th>
      <th>Valor total generado</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$apartamentos): ?>
      <tr><td colspan="8" class="empty-state">Aún no has registrado apartamentos.</td></tr>
    <?php endif; ?>
    <?php foreach ($apartamentos as $a): ?>
      <tr>
        <td>
          <?php if ($a['foto_portada']): ?>
            <img src="assets/uploads/apartamentos/<?= e($a['foto_portada']) ?>" alt="" class="list-thumb">
          <?php endif; ?>
        </td>
        <td><?= e($a['nombre']) ?></td>
        <td><?= e($a['propietario'] ?? '') ?></td>
        <td>
          <?php
            $rasgos = [];
            if ($a['habitaciones'] !== null) $rasgos[] = $a['habitaciones'] . ' hab.';
            if ($a['cocinas'] !== null) $rasgos[] = $a['cocinas'] . ' cocina(s)';
            if ($a['banos'] !== null) $rasgos[] = $a['banos'] . ' baño(s)';
            if ($a['capacidad_huespedes'] !== null) $rasgos[] = 'hasta ' . $a['capacidad_huespedes'] . ' huéspedes';
            echo e($rasgos ? implode(' · ', $rasgos) : '—');
          ?>
        </td>
        <td>
          <?php if ($a['precio_noche'] !== null): ?>
            <?= formatCOP($a['precio_noche']) ?>
            <?php if ($a['precio_fin_semana'] !== null): ?>
              <br><span style="font-size:11px; color:var(--text-muted);">Finde: <?= formatCOP($a['precio_fin_semana']) ?></span>
            <?php endif; ?>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
        <td><?= (int) $a['total_reservas'] ?></td>
        <td><?= formatCOP($a['total_valor']) ?></td>
        <td class="actions-cell">
          <a href="apartamento_form.php?id=<?= (int) $a['id'] ?>" class="btn-link">Editar</a>
          <form method="post" action="apartamento_delete.php" onsubmit="return confirm('¿Eliminar este apartamento?');" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
            <button type="submit" class="btn-link btn-link-danger">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
