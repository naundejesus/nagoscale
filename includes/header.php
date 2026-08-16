<?php
declare(strict_types=1);
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'Inmuebles por Días';

$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM solicitudes s
     JOIN apartamentos a ON a.id = s.apartamento_id
     WHERE a.user_id = ? AND s.estado = 'pendiente'"
);
$stmt->execute([current_user_id()]);
$solicitudesPendientes = (int) $stmt->fetchColumn();
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e(brand_name()) ?></title>
<link rel="icon" type="image/png" href="assets/img/favicon.png">
<link rel="stylesheet" href="assets/css/style.css?v=<?= e(asset_version()) ?>">
<?php require __DIR__ . '/brand_style.php'; ?>
</head>
<body>
<header class="site-header">
  <?php require __DIR__ . '/banner.php'; ?>
  <nav class="main-nav">
    <div class="main-nav-links">
      <a href="reservas.php"<?= basename($_SERVER['PHP_SELF']) === 'reservas.php' || basename($_SERVER['PHP_SELF']) === 'reserva_form.php' || basename($_SERVER['PHP_SELF']) === 'index.php' ? ' class="active"' : '' ?>>Reservas</a>
      <a href="apartamentos.php"<?= basename($_SERVER['PHP_SELF']) === 'apartamentos.php' || basename($_SERVER['PHP_SELF']) === 'apartamento_form.php' ? ' class="active"' : '' ?>>Apartamentos</a>
      <a href="solicitudes.php"<?= in_array(basename($_SERVER['PHP_SELF']), ['solicitudes.php', 'solicitud_aprobar.php'], true) ? ' class="active"' : '' ?>>
        Solicitudes
        <?php if ($solicitudesPendientes > 0): ?><span class="nav-badge"><?= $solicitudesPendientes ?></span><?php endif; ?>
      </a>
    </div>
    <div class="main-nav-user">
      <span>Hola, <?= e($_SESSION['username'] ?? '') ?></span>
      <a href="perfil.php" class="btn-link">Mi perfil</a>
      <a href="logout.php" class="btn-link">Cerrar sesión</a>
    </div>
  </nav>
</header>
<main class="container">
