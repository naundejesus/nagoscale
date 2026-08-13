<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

$id = null;
if (isset($_GET['id']) && ctype_digit((string) $_GET['id'])) {
    $id = (int) $_GET['id'];
} elseif (isset($_POST['id']) && ctype_digit((string) $_POST['id'])) {
    $id = (int) $_POST['id'];
}

$apartamento = ['nombre' => '', 'direccion' => ''];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM apartamentos WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if (!$found) {
        http_response_code(404);
        die('Apartamento no encontrado.');
    }
    $apartamento = $found;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $direccion = trim((string) ($_POST['direccion'] ?? ''));

    if ($nombre === '') {
        $error = 'El nombre del apartamento es obligatorio.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE apartamentos SET nombre = ?, direccion = ? WHERE id = ?');
            $stmt->execute([$nombre, $direccion ?: null, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO apartamentos (nombre, direccion) VALUES (?, ?)');
            $stmt->execute([$nombre, $direccion ?: null]);
        }
        header('Location: apartamentos.php?guardado=1');
        exit;
    }

    $apartamento = ['nombre' => $nombre, 'direccion' => $direccion];
}

$pageTitle = $id ? 'Editar apartamento' : 'Nuevo apartamento';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <h1><?= $id ? 'Editar apartamento' : 'Nuevo apartamento' ?></h1>
  <a href="apartamentos.php" class="btn-link">&larr; Volver a apartamentos</a>
</div>

<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>

<form class="form-card" method="post">
  <?= csrf_field() ?>
  <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

  <label>Nombre del apartamento
    <input type="text" name="nombre" value="<?= e($apartamento['nombre']) ?>" required autofocus>
  </label>

  <label>Dirección (opcional)
    <input type="text" name="direccion" value="<?= e($apartamento['direccion'] ?? '') ?>">
  </label>

  <button type="submit" class="btn btn-primary">Guardar apartamento</button>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
