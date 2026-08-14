<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if (current_user_id()) {
    header('Location: reservas.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $password2 = (string) ($_POST['password2'] ?? '');

    if ($username === '' || mb_strlen($username) < 3) {
        $error = 'El usuario debe tener al menos 3 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Escribe un correo electrónico válido (te avisaremos ahí de nuevas solicitudes de reserva).';
    } elseif (mb_strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($password !== $password2) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Ese usuario ya existe, elige otro.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)');
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $userId = (int) $pdo->lastInsertId();

            session_regenerate_id(true);
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            header('Location: reservas.php?bienvenida=1');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Crear cuenta · NagoScale</title>
<link rel="icon" type="image/png" href="assets/img/favicon.png">
<link rel="stylesheet" href="assets/css/style.css?v=<?= e(asset_version()) ?>">
</head>
<body class="auth-body">
<?php require __DIR__ . '/includes/banner.php'; ?>
<div class="auth-wrap">
  <form class="auth-card" method="post" novalidate>
    <h1>Crear cuenta nueva</h1>
    <p class="auth-subtitle">Empieza desde cero con tus propios apartamentos y reservas.</p>
    <?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
    <?= csrf_field() ?>
    <label>Usuario
      <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" required minlength="3" autofocus>
    </label>
    <label>Correo electrónico
      <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
    </label>
    <label>Contraseña
      <input type="password" name="password" required minlength="8">
    </label>
    <label>Confirmar contraseña
      <input type="password" name="password2" required minlength="8">
    </label>
    <button type="submit" class="btn btn-primary">Crear cuenta</button>
    <p class="auth-subtitle">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
  </form>
</div>
</body>
</html>
