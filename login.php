<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (current_user_id()) {
    header('Location: reservas.php');
    exit;
}

$error = '';
$creado = isset($_GET['creado']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: reservas.php');
        exit;
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión · <?= e(brand_name()) ?></title>
<link rel="icon" type="image/png" href="assets/img/favicon.png">
<link rel="stylesheet" href="assets/css/style.css?v=<?= e(asset_version()) ?>">
<?php require __DIR__ . '/includes/brand_style.php'; ?>
</head>
<body class="auth-body">
<?php require __DIR__ . '/includes/banner.php'; ?>
<div class="auth-wrap">
  <form class="auth-card" method="post" novalidate>
    <h1>Inmuebles por Días</h1>
    <p class="auth-subtitle">Ingresa con tu usuario y contraseña.</p>
    <?php if ($creado): ?><p class="alert alert-success">Cuenta creada. Ya puedes iniciar sesión.</p><?php endif; ?>
    <?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
    <?= csrf_field() ?>
    <label>Usuario
      <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus>
    </label>
    <label>Contraseña
      <input type="password" name="password" required>
    </label>
    <button type="submit" class="btn btn-primary">Entrar</button>
    <p class="auth-subtitle">¿No tienes cuenta? <a href="register.php">Regístrate</a></p>
  </form>
</div>
</body>
</html>
