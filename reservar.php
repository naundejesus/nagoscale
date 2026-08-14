<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$userId = (int) ($_GET['u'] ?? 0);

if ($userId <= 0) {
    http_response_code(404);
    die('Página no encontrada.');
}

$stmt = $pdo->prepare('SELECT id, username FROM users WHERE id = ?');
$stmt->execute([$userId]);
$cuenta = $stmt->fetch();
if (!$cuenta) {
    http_response_code(404);
    die('Página no encontrada.');
}

$stmt = $pdo->prepare(
    'SELECT a.*,
            (SELECT archivo FROM apartamento_fotos f WHERE f.apartamento_id = a.id ORDER BY f.id LIMIT 1) AS foto_portada
     FROM apartamentos a
     WHERE a.user_id = ?
     ORDER BY a.nombre'
);
$stmt->execute([$userId]);
$apartamentos = $stmt->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Apartamentos disponibles · NagoScale</title>
<link rel="icon" type="image/png" href="assets/img/favicon.png">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php require __DIR__ . '/includes/banner.php'; ?>
<main class="container">

<div class="page-head">
  <h1>Apartamentos disponibles</h1>
</div>

<?php if (!$apartamentos): ?>
  <p class="alert alert-error">Todavía no hay apartamentos publicados aquí.</p>
<?php else: ?>
  <div class="public-grid">
    <?php foreach ($apartamentos as $a): ?>
      <div class="public-card">
        <?php if ($a['foto_portada']): ?>
          <img src="assets/uploads/apartamentos/<?= e($a['foto_portada']) ?>" alt="<?= e($a['nombre']) ?>" class="public-card-img">
        <?php else: ?>
          <div class="public-card-img public-card-img-placeholder"></div>
        <?php endif; ?>
        <div class="public-card-body">
          <h2><?= e($a['nombre']) ?></h2>
          <?php if ($a['direccion']): ?><p class="public-card-direccion"><?= e($a['direccion']) ?></p><?php endif; ?>
          <a href="reservar_form.php?apartamento_id=<?= (int) $a['id'] ?>" class="btn btn-primary">Solicitar reserva</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

</main>
<footer class="site-footer">
  <p>NagoScale · Inmuebles por Días</p>
</footer>
</body>
</html>
