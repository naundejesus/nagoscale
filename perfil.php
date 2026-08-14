<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([current_user_id()]);
$usuario = $stmt->fetch();

$error = '';
$guardado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Escribe un correo electrónico válido.';
    } else {
        $stmt = $pdo->prepare('UPDATE users SET email = ? WHERE id = ?');
        $stmt->execute([$email, current_user_id()]);
        $usuario['email'] = $email;
        $guardado = true;
    }
}

$linkPublico = rtrim(APP_URL, '/') . '/reservar.php?u=' . current_user_id();

$pageTitle = 'Mi perfil';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <h1>Mi perfil</h1>
</div>

<?php if ($guardado): ?>
  <p class="alert alert-success">Correo actualizado.</p>
<?php endif; ?>
<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>

<form class="form-card" method="post">
  <?= csrf_field() ?>
  <label>Usuario
    <input type="text" value="<?= e($usuario['username']) ?>" disabled>
  </label>
  <label>Correo electrónico (para notificarte de nuevas solicitudes)
    <input type="email" name="email" value="<?= e($usuario['email'] ?? '') ?>" required>
  </label>
  <button type="submit" class="btn btn-primary">Guardar</button>
</form>

<div class="form-card" style="margin-top:16px;">
  <span style="font-size:13px; color:var(--text-muted); font-weight:600;">Tu link público para clientes</span>
  <p style="font-size:14px; word-break:break-all; margin:8px 0;"><?= e($linkPublico) ?></p>
  <p style="font-size:12px; color:var(--text-muted); margin:0;">
    Compártelo con tus clientes: verán tus apartamentos disponibles y podrán solicitar una reserva directamente.
  </p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
