<?php
declare(strict_types=1);
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'Inmuebles por Días';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · NagoScale</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
  <?php require __DIR__ . '/banner.php'; ?>
  <nav class="main-nav">
    <div class="main-nav-links">
      <a href="reservas.php"<?= basename($_SERVER['PHP_SELF']) === 'reservas.php' || basename($_SERVER['PHP_SELF']) === 'reserva_form.php' || basename($_SERVER['PHP_SELF']) === 'index.php' ? ' class="active"' : '' ?>>Reservas</a>
      <a href="apartamentos.php"<?= basename($_SERVER['PHP_SELF']) === 'apartamentos.php' || basename($_SERVER['PHP_SELF']) === 'apartamento_form.php' ? ' class="active"' : '' ?>>Apartamentos</a>
    </div>
    <div class="main-nav-user">
      <span>Hola, <?= e($_SESSION['username'] ?? '') ?></span>
      <a href="logout.php" class="btn-link">Cerrar sesión</a>
    </div>
  </nav>
</header>
<main class="container">
