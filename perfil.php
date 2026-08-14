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
    $nequiNumero = trim($_POST['nequi_numero'] ?? '');
    $bancolombiaTipoCuenta = trim($_POST['bancolombia_tipo_cuenta'] ?? '');
    $bancolombiaNumero = trim($_POST['bancolombia_numero'] ?? '');
    $bancolombiaTitular = trim($_POST['bancolombia_titular'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Escribe un correo electrónico válido.';
    } else {
        $stmt = $pdo->prepare(
            'UPDATE users
             SET email = ?, nequi_numero = ?, bancolombia_tipo_cuenta = ?, bancolombia_numero = ?, bancolombia_titular = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $email,
            $nequiNumero ?: null,
            $bancolombiaTipoCuenta ?: null,
            $bancolombiaNumero ?: null,
            $bancolombiaTitular ?: null,
            current_user_id(),
        ]);
        $usuario = array_merge($usuario, [
            'email' => $email,
            'nequi_numero' => $nequiNumero,
            'bancolombia_tipo_cuenta' => $bancolombiaTipoCuenta,
            'bancolombia_numero' => $bancolombiaNumero,
            'bancolombia_titular' => $bancolombiaTitular,
        ]);
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
  <p class="alert alert-success">Datos actualizados.</p>
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

  <hr style="border:none; border-top:1px solid var(--border); margin:0;">
  <span style="font-size:13px; color:var(--text-muted); font-weight:600;">
    Datos de pago (se muestran a tus clientes para que abonen el 50% al reservar)
  </span>

  <label>Nequi — número de celular
    <input type="text" name="nequi_numero" value="<?= e($usuario['nequi_numero'] ?? '') ?>" placeholder="300 000 0000">
  </label>

  <div class="form-grid-2">
    <label>Bancolombia — tipo de cuenta
      <select name="bancolombia_tipo_cuenta">
        <option value="">Selecciona...</option>
        <option value="Ahorros" <?= ($usuario['bancolombia_tipo_cuenta'] ?? '') === 'Ahorros' ? 'selected' : '' ?>>Ahorros</option>
        <option value="Corriente" <?= ($usuario['bancolombia_tipo_cuenta'] ?? '') === 'Corriente' ? 'selected' : '' ?>>Corriente</option>
      </select>
    </label>
    <label>Bancolombia — número de cuenta
      <input type="text" name="bancolombia_numero" value="<?= e($usuario['bancolombia_numero'] ?? '') ?>">
    </label>
  </div>

  <label>Bancolombia — nombre del titular
    <input type="text" name="bancolombia_titular" value="<?= e($usuario['bancolombia_titular'] ?? '') ?>">
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
