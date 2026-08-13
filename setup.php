<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

// Esta página solo funciona mientras no exista ningún usuario. Una vez
// creado el primer administrador, se desactiva automáticamente.
if (user_count($pdo) > 0) {
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password2'] ?? '');

    if ($username === '' || mb_strlen($username) < 3) {
        $error = 'El usuario debe tener al menos 3 caracteres.';
    } elseif (mb_strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $password2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
        $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
        header('Location: login.php?creado=1');
        exit;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Crear administrador · NagoScale</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
<?php require __DIR__ . '/includes/banner.php'; ?>
<div class="auth-wrap">
  <form class="auth-card" method="post" novalidate>
    <h1>Crear cuenta de administrador</h1>
    <p class="auth-subtitle">Primer ingreso: crea el usuario que usarás para entrar al sistema.</p>
    <?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
    <?= csrf_field() ?>
    <label>Usuario
      <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" required minlength="3" autofocus>
    </label>
    <label>Contraseña
      <input type="password" name="password" required minlength="8">
    </label>
    <label>Confirmar contraseña
      <input type="password" name="password2" required minlength="8">
    </label>
    <button type="submit" class="btn btn-primary">Crear cuenta</button>
  </form>
</div>
</body>
</html>
