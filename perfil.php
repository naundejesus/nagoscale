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
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $nequiNumero = trim($_POST['nequi_numero'] ?? '');
    $bancolombiaTipoCuenta = trim($_POST['bancolombia_tipo_cuenta'] ?? '');
    $bancolombiaNumero = trim($_POST['bancolombia_numero'] ?? '');
    $bancolombiaTitular = trim($_POST['bancolombia_titular'] ?? '');
    $mostrarDatosPagoPublico = isset($_POST['mostrar_datos_pago_publico']) ? 1 : 0;

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Escribe un correo electrónico válido.';
    } else {
        $stmt = $pdo->prepare(
            'UPDATE users
             SET email = ?, whatsapp = ?, nequi_numero = ?, bancolombia_tipo_cuenta = ?, bancolombia_numero = ?, bancolombia_titular = ?, mostrar_datos_pago_publico = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $email,
            $whatsapp ?: null,
            $nequiNumero ?: null,
            $bancolombiaTipoCuenta ?: null,
            $bancolombiaNumero ?: null,
            $bancolombiaTitular ?: null,
            $mostrarDatosPagoPublico,
            current_user_id(),
        ]);
        $usuario = array_merge($usuario, [
            'email' => $email,
            'whatsapp' => $whatsapp,
            'nequi_numero' => $nequiNumero,
            'bancolombia_tipo_cuenta' => $bancolombiaTipoCuenta,
            'bancolombia_numero' => $bancolombiaNumero,
            'bancolombia_titular' => $bancolombiaTitular,
            'mostrar_datos_pago_publico' => $mostrarDatosPagoPublico,
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
  <label>WhatsApp de contacto (se muestra a tus clientes en la página de cada alojamiento)
    <input type="text" name="whatsapp" value="<?= e($usuario['whatsapp'] ?? '') ?>" placeholder="300 000 0000">
  </label>

  <hr style="border:none; border-top:1px solid var(--border); margin:0;">
  <span style="font-size:13px; color:var(--text-muted); font-weight:600;">
    Datos de pago
  </span>

  <label class="ne-checkbox" style="display:flex; align-items:center; gap:8px;">
    <input type="checkbox" name="mostrar_datos_pago_publico" <?= !empty($usuario['mostrar_datos_pago_publico']) ? 'checked' : '' ?>>
    Mostrar mis datos de pago a los huéspedes en la página pública de mis alojamientos
  </label>
  <p style="font-size:12px; color:var(--text-muted); margin:0;">
    Mientras esta opción esté desactivada, tu Nequi y Bancolombia se guardan pero NO se muestran a nadie en la página pública. Tu WhatsApp de contacto siempre es visible para tus clientes, aunque esta opción esté desactivada.
  </p>

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
    Compártelo con tus clientes: verán tus alojamientos disponibles y podrán solicitar una reserva directamente.
  </p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
