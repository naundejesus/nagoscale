<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
require_login();

const MAX_FOTOS_APARTAMENTO = 20;

$id = null;
if (isset($_GET['id']) && ctype_digit((string) $_GET['id'])) {
    $id = (int) $_GET['id'];
} elseif (isset($_POST['id']) && ctype_digit((string) $_POST['id'])) {
    $id = (int) $_POST['id'];
}

$apartamento = [
    'nombre' => '',
    'propietario' => '',
    'direccion' => '',
    'habitaciones' => '',
    'cocinas' => '',
    'banos' => '',
    'capacidad_huespedes' => '',
    'precio_noche' => '',
    'precio_fin_semana' => '',
];
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

function entero_opcional($valor): ?int
{
    $valor = trim((string) $valor);
    return $valor === '' ? null : (int) $valor;
}

function decimal_opcional($valor): ?float
{
    $valor = trim((string) $valor);
    return $valor === '' ? null : (float) $valor;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $propietario = trim((string) ($_POST['propietario'] ?? ''));
    $direccion = trim((string) ($_POST['direccion'] ?? ''));
    $habitaciones = entero_opcional($_POST['habitaciones'] ?? '');
    $cocinas = entero_opcional($_POST['cocinas'] ?? '');
    $banos = entero_opcional($_POST['banos'] ?? '');
    $capacidadHuespedes = entero_opcional($_POST['capacidad_huespedes'] ?? '');
    $precioNoche = decimal_opcional($_POST['precio_noche'] ?? '');
    $precioFinSemana = decimal_opcional($_POST['precio_fin_semana'] ?? '');

    if ($nombre === '') {
        $error = 'El nombre del apartamento es obligatorio.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE apartamentos
                 SET nombre = ?, propietario = ?, direccion = ?, habitaciones = ?, cocinas = ?,
                     banos = ?, capacidad_huespedes = ?, precio_noche = ?, precio_fin_semana = ?
                 WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([
                $nombre, $propietario ?: null, $direccion ?: null, $habitaciones, $cocinas,
                $banos, $capacidadHuespedes, $precioNoche, $precioFinSemana, $id, current_user_id(),
            ]);
            $apartamentoId = $id;
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO apartamentos
                    (user_id, nombre, propietario, direccion, habitaciones, cocinas, banos, capacidad_huespedes, precio_noche, precio_fin_semana)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                current_user_id(), $nombre, $propietario ?: null, $direccion ?: null, $habitaciones,
                $cocinas, $banos, $capacidadHuespedes, $precioNoche, $precioFinSemana,
            ]);
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

        // Subir fotos nuevas (máximo MAX_FOTOS_APARTAMENTO por apartamento)
        if (!empty($_FILES['fotos']['name'][0])) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM apartamento_fotos WHERE apartamento_id = ?');
            $stmt->execute([$apartamentoId]);
            $totalFotos = (int) $stmt->fetchColumn();

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            foreach ($_FILES['fotos']['tmp_name'] as $i => $tmpName) {
                if ($totalFotos >= MAX_FOTOS_APARTAMENTO) {
                    break;
                }
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
                    $totalFotos++;
                }
            }
        }

        header('Location: apartamentos.php?guardado=1');
        exit;
    }

    $apartamento = [
        'nombre' => $nombre,
        'propietario' => $propietario,
        'direccion' => $direccion,
        'habitaciones' => $habitaciones,
        'cocinas' => $cocinas,
        'banos' => $banos,
        'capacidad_huespedes' => $capacidadHuespedes,
        'precio_noche' => $precioNoche,
        'precio_fin_semana' => $precioFinSemana,
    ];
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

  <div class="form-grid-2">
    <label>Habitaciones
      <input type="number" name="habitaciones" min="0" value="<?= e($apartamento['habitaciones'] ?? '') ?>">
    </label>
    <label>Cocinas
      <input type="number" name="cocinas" min="0" value="<?= e($apartamento['cocinas'] ?? '') ?>">
    </label>
    <label>Baños
      <input type="number" name="banos" min="0" value="<?= e($apartamento['banos'] ?? '') ?>">
    </label>
    <label>Capacidad máxima de huéspedes
      <input type="number" name="capacidad_huespedes" min="1" value="<?= e($apartamento['capacidad_huespedes'] ?? '') ?>">
    </label>
  </div>

  <div class="form-grid-2">
    <label>Precio por noche entre semana (COP)
      <input type="number" name="precio_noche" min="0" step="1" value="<?= e($apartamento['precio_noche'] ?? '') ?>">
    </label>
    <label>Precio por noche fin de semana - viernes y sábado (opcional)
      <input type="number" name="precio_fin_semana" min="0" step="1" value="<?= e($apartamento['precio_fin_semana'] ?? '') ?>">
    </label>
  </div>
  <p class="cal-hint">Estos precios se usan para mostrarle al cliente el valor total estimado en el formulario público de reservas. Si dejas el precio de fin de semana vacío, se usa el mismo precio entre semana.</p>

  <?php if ($fotos): ?>
    <div>
      <span style="display:block; font-size:13px; color:var(--text-muted); font-weight:600; margin-bottom:8px;">
        Fotos actuales (<?= count($fotos) ?>/<?= MAX_FOTOS_APARTAMENTO ?>)
      </span>
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

  <label>Agregar fotos (opcional, máximo <?= MAX_FOTOS_APARTAMENTO ?> en total)
    <input type="file" name="fotos[]" accept="image/png,image/jpeg,image/webp" multiple>
  </label>

  <button type="submit" class="btn btn-primary">Guardar apartamento</button>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
