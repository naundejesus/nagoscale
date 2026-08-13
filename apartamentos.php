<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$apartamentos = $pdo->query(
    'SELECT a.*,
            COUNT(r.id) AS total_reservas,
            COALESCE(SUM(r.valor_total), 0) AS total_valor
     FROM apartamentos a
     LEFT JOIN reservas r ON r.apartamento_id = a.id
     GROUP BY a.id
     ORDER BY a.nombre'
)->fetchAll();

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
      <th>Nombre</th>
      <th>Dirección</th>
      <th>Reservas registradas</th>
      <th>Valor total generado</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$apartamentos): ?>
      <tr><td colspan="5" class="empty-state">Aún no has registrado apartamentos.</td></tr>
    <?php endif; ?>
    <?php foreach ($apartamentos as $a): ?>
      <tr>
        <td><?= e($a['nombre']) ?></td>
        <td><?= e($a['direccion'] ?? '') ?></td>
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
