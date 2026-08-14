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

$apartamento = ['nombre' => '', 'propietario' => '', 'direccion' => ''];
$fotos = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM apartamentos WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, current_user_id()]);
    $found = $stmt->fetch();
    if (!$found) {
        http_response_code(404);
        die('Apartamento no encontrado.');
    }
    $apartamento = $found;

    $stmt = $pdo->prepare('SELECT * FROM apartamento_fotos WHERE apartamento_id = ? ORDER BY id');
    $stmt->execute([$id]);
    $fotos = $stmt->fetchAll();
}

$error = '';
$uploadDir = __DIR__ . '/assets/uploads/apartamentos/';
$mimeExtensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $propietario = trim((string) ($_POST['propietario'] ?? ''));
    $direccion = trim((string) ($_POST['direccion'] ?? ''));

    if ($nombre === '') {
        $error = 'El nombre del apartamento es obligatorio.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE apartamentos SET nombre = ?, propietario = ?, direccion = ? WHERE id = ? AND user_id = ?');
            $stmt->execute([$nombre, $propietario ?: null, $direccion ?: null, $id, current_user_id()]);
            $apartamentoId = $id;
        } else {
            $stmt = $pdo->prepare('INSERT INTO apartamentos (user_id, nombre, propietario, direccion) VALUES (?, ?, ?, ?)');
            $stmt->execute([current_user_id(), $nombre, $propietario ?: null, $direccion ?: null]);
            $apartamentoId = (int) $pdo->lastInsertId();
        }

        // Eliminar fotos marcadas para borrar
        $eliminarFotos = $_POST['eliminar_foto'] ?? [];
        if (is_array($eliminarFotos) && $eliminarFotos) {
            $ids = array_map('intval', $eliminarFotos);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT * FROM apartamento_fotos WHERE apartamento_id = ? AND id IN ($placeholders)");
            $stmt->execute([$apartamentoId, ...$ids]);
            foreach ($stmt->fetchAll() as $foto) {
                $ruta = $uploadDir . $foto['archivo'];
                if (is_file($ruta)) {
                    unlink($ruta);
                }
            }
            $stmt = $pdo->prepare("DELETE FROM apartamento_fotos WHERE apartamento_id = ? AND id IN ($placeholders)");
            $stmt->execute([$apartamentoId, ...$ids]);
        }

        // Subir fotos nuevas
        if (!empty($_FILES['fotos']['name'][0])) {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            foreach ($_FILES['fotos']['tmp_name'] as $i => $tmpName) {
                if ($_FILES['fotos']['error'][$i] !== UPLOAD_ERR_OK || $tmpName === '') {
                    continue;
                }
                $mime = mime_content_type($tmpName);
                if (!isset($mimeExtensiones[$mime])) {
                    continue;
                }
                $archivo = bin2hex(random_bytes(16)) . '.' . $mimeExtensiones[$mime];
                if (move_uploaded_file($tmpName, $uploadDir . $archivo)) {
                    $stmt = $pdo->prepare('INSERT INTO apartamento_fotos (apartamento_id, archivo) VALUES (?, ?)');
                    $stmt->execute([$apartamentoId, $archivo]);
                }
            }
        }

        header('Location: apartamentos.php?guardado=1');
        exit;
    }

    $apartamento = ['nombre' => $nombre, 'propietario' => $propietario, 'direccion' => $direccion];
}

$pageTitle = $id ? 'Editar apartamento' : 'Nuevo apartamento';
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <h1><?= $id ? 'Editar apartamento' : 'Nuevo apartamento' ?></h1>
  <a href="apartamentos.php" class="btn-link">&larr; Volver a apartamentos</a>
</div>

<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>

<form class="form-card" method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>

  <label>Nombre del apartamento
    <input type="text" name="nombre" value="<?= e($apartamento['nombre']) ?>" required autofocus>
  </label>

  <label>Nombre del propietario (opcional)
    <input type="text" name="propietario" value="<?= e($apartamento['propietario'] ?? '') ?>">
  </label>

  <label>Dirección (opcional)
    <input type="text" name="direccion" value="<?= e($apartamento['direccion'] ?? '') ?>">
  </label>

  <?php if ($fotos): ?>
    <div>
      <span style="display:block; font-size:13px; color:var(--text-muted); font-weight:600; margin-bottom:8px;">Fotos actuales</span>
      <div class="photo-grid">
        <?php foreach ($fotos as $foto): ?>
          <label class="photo-thumb">
            <img src="assets/uploads/apartamentos/<?= e($foto['archivo']) ?>" alt="Foto del apartamento">
            <span><input type="checkbox" name="eliminar_foto[]" value="<?= (int) $foto['id'] ?>"> Eliminar</span>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <label>Agregar fotos (opcional)
    <input type="file" name="fotos[]" accept="image/png,image/jpeg,image/webp" multiple>
  </label>

  <button type="submit" class="btn btn-primary">Guardar apartamento</button>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
