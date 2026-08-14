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
    'ciudad' => '',
    'zona' => '',
    'descripcion' => '',
    'habitaciones' => '',
    'cocinas' => '',
    'banos' => '',
    'capacidad_huespedes' => '',
    'precio_noche' => '',
    'precio_fin_semana' => '',
    'latitud' => '',
    'longitud' => '',
];
$amenidadesSeleccionadas = [];
$fotos = [];
$resenas = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM apartamentos WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, current_user_id()]);
    $found = $stmt->fetch();
    if (!$found) {
        http_response_code(404);
        die('Apartamento no encontrado.');
    }
    $apartamento = $found;
    $amenidadesSeleccionadas = decodificar_amenidades($found['amenidades'] ?? null);

    $stmt = $pdo->prepare('SELECT * FROM apartamento_fotos WHERE apartamento_id = ? ORDER BY id');
    $stmt->execute([$id]);
    $fotos = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT * FROM resenas WHERE apartamento_id = ? ORDER BY created_at DESC');
    $stmt->execute([$id]);
    $resenas = $stmt->fetchAll();
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
    $ciudad = trim((string) ($_POST['ciudad'] ?? ''));
    $zona = trim((string) ($_POST['zona'] ?? ''));
    $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
    $habitaciones = entero_opcional($_POST['habitaciones'] ?? '');
    $cocinas = entero_opcional($_POST['cocinas'] ?? '');
    $banos = entero_opcional($_POST['banos'] ?? '');
    $capacidadHuespedes = entero_opcional($_POST['capacidad_huespedes'] ?? '');
    $precioNoche = decimal_opcional($_POST['precio_noche'] ?? '');
    $precioFinSemana = decimal_opcional($_POST['precio_fin_semana'] ?? '');
    $latitud = decimal_opcional($_POST['latitud'] ?? '');
    $longitud = decimal_opcional($_POST['longitud'] ?? '');
    $amenidadesSeleccionadas = array_intersect(
        (array) ($_POST['amenidades'] ?? []),
        array_keys(amenidades_disponibles())
    );
    $amenidadesJson = $amenidadesSeleccionadas ? json_encode(array_values($amenidadesSeleccionadas)) : null;

    if ($nombre === '') {
        $error = 'El nombre del apartamento es obligatorio.';
    } else {
        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE apartamentos
                 SET nombre = ?, propietario = ?, direccion = ?, ciudad = ?, zona = ?, descripcion = ?,
                     habitaciones = ?, cocinas = ?, banos = ?, capacidad_huespedes = ?,
                     precio_noche = ?, precio_fin_semana = ?, amenidades = ?, latitud = ?, longitud = ?
                 WHERE id = ? AND user_id = ?'
            );
            $stmt->execute([
                $nombre, $propietario ?: null, $direccion ?: null, $ciudad ?: null, $zona ?: null, $descripcion ?: null,
                $habitaciones, $cocinas, $banos, $capacidadHuespedes,
                $precioNoche, $precioFinSemana, $amenidadesJson, $latitud, $longitud,
                $id, current_user_id(),
            ]);
            $apartamentoId = $id;
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO apartamentos
                    (user_id, nombre, propietario, direccion, ciudad, zona, descripcion, habitaciones, cocinas,
                     banos, capacidad_huespedes, precio_noche, precio_fin_semana, amenidades, latitud, longitud)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                current_user_id(), $nombre, $propietario ?: null, $direccion ?: null, $ciudad ?: null, $zona ?: null,
                $descripcion ?: null, $habitaciones, $cocinas, $banos, $capacidadHuespedes,
                $precioNoche, $precioFinSemana, $amenidadesJson, $latitud, $longitud,
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
        'ciudad' => $ciudad,
        'zona' => $zona,
        'descripcion' => $descripcion,
        'habitaciones' => $habitaciones,
        'cocinas' => $cocinas,
        'banos' => $banos,
        'capacidad_huespedes' => $capacidadHuespedes,
        'precio_noche' => $precioNoche,
        'precio_fin_semana' => $precioFinSemana,
        'latitud' => $latitud,
        'longitud' => $longitud,
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

<form class="form-card" method="post" enctype="multipart/form-data" style="max-width:640px;">
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
    <label>Ciudad
      <input type="text" name="ciudad" value="<?= e($apartamento['ciudad'] ?? '') ?>" placeholder="Medellín">
    </label>
    <label>Zona / barrio
      <input type="text" name="zona" value="<?= e($apartamento['zona'] ?? '') ?>" placeholder="El Poblado">
    </label>
  </div>

  <label>Descripción (se muestra a los clientes)
    <textarea name="descripcion" rows="4" style="padding:11px 14px; border-radius:8px; border:1.5px solid var(--border); font-size:14px; font-family:inherit; resize:vertical;"><?= e($apartamento['descripcion'] ?? '') ?></textarea>
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

  <div>
    <span style="display:block; font-size:13px; color:var(--text-secondary); font-weight:600; margin-bottom:8px;">Servicios</span>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px;">
      <?php foreach (amenidades_disponibles() as $key => $info): ?>
        <label class="ne-checkbox">
          <input type="checkbox" name="amenidades[]" value="<?= e($key) ?>" <?= in_array($key, $amenidadesSeleccionadas, true) ? 'checked' : '' ?>>
          <?= $info['icon'] ?> <?= e($info['label']) ?>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div>
    <span style="display:block; font-size:13px; color:var(--text-secondary); font-weight:600; margin-bottom:8px;">Ubicación en el mapa (opcional)</span>
    <div id="mapa-picker" style="height:240px; border-radius:8px; border:1px solid var(--border);"></div>
    <p class="cal-hint">Haz clic en el mapa para marcar la ubicación exacta del apartamento.</p>
    <input type="hidden" name="latitud" id="input-latitud" value="<?= e($apartamento['latitud'] ?? '') ?>">
    <input type="hidden" name="longitud" id="input-longitud" value="<?= e($apartamento['longitud'] ?? '') ?>">
  </div>

  <?php if ($fotos): ?>
    <div>
      <span style="display:block; font-size:13px; color:var(--text-secondary); font-weight:600; margin-bottom:8px;">
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

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  var latInicial = <?= $apartamento['latitud'] !== null && $apartamento['latitud'] !== '' ? (float) $apartamento['latitud'] : 6.2442 ?>;
  var lngInicial = <?= $apartamento['longitud'] !== null && $apartamento['longitud'] !== '' ? (float) $apartamento['longitud'] : -75.5812 ?>;
  var mapaPicker = L.map('mapa-picker').setView([latInicial, lngInicial], 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(mapaPicker);
  var marcadorPicker = null;
  <?php if ($apartamento['latitud'] !== null && $apartamento['latitud'] !== ''): ?>
    marcadorPicker = L.marker([latInicial, lngInicial]).addTo(mapaPicker);
  <?php endif; ?>
  mapaPicker.on('click', function (e) {
    if (marcadorPicker) {
      mapaPicker.removeLayer(marcadorPicker);
    }
    marcadorPicker = L.marker(e.latlng).addTo(mapaPicker);
    document.getElementById('input-latitud').value = e.latlng.lat.toFixed(7);
    document.getElementById('input-longitud').value = e.latlng.lng.toFixed(7);
  });
</script>

<?php if ($id): ?>
<div class="form-card" style="max-width:640px; margin-top:20px;">
  <h2 style="font-size:16px;">Reseñas de clientes</h2>
  <p class="cal-hint">Como los clientes reservan sin crear cuenta, tú puedes cargar aquí las reseñas que te compartan (por WhatsApp, Airbnb, etc.) para que se vean en la ficha pública del apartamento.</p>

  <?php if ($resenas): ?>
    <div style="display:flex; flex-direction:column; gap:10px;">
      <?php foreach ($resenas as $r): ?>
        <div style="border:1px solid var(--border); border-radius:8px; padding:10px 12px; display:flex; justify-content:space-between; align-items:start; gap:10px;">
          <div>
            <strong><?= e($r['nombre_cliente']) ?></strong> — <span class="ne-stars"><?= estrellas_html((float) $r['calificacion']) ?></span>
            <?php if ($r['comentario']): ?><p style="margin:4px 0 0; font-size:13px; color:var(--text-secondary);"><?= e($r['comentario']) ?></p><?php endif; ?>
          </div>
          <form method="post" action="resena_eliminar.php" onsubmit="return confirm('¿Eliminar esta reseña?');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
            <button type="submit" class="btn-link btn-link-danger">Eliminar</button>
          </form>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" action="resena_agregar.php" style="display:flex; flex-direction:column; gap:12px; margin-top:16px;">
    <?= csrf_field() ?>
    <input type="hidden" name="apartamento_id" value="<?= (int) $id ?>">
    <div class="form-grid-2">
      <label>Nombre del cliente
        <input type="text" name="nombre_cliente" required>
      </label>
      <label>Calificación
        <select name="calificacion" required>
          <option value="5">5 estrellas</option>
          <option value="4">4 estrellas</option>
          <option value="3">3 estrellas</option>
          <option value="2">2 estrellas</option>
          <option value="1">1 estrella</option>
        </select>
      </label>
    </div>
    <label>Comentario (opcional)
      <input type="text" name="comentario" maxlength="500">
    </label>
    <button type="submit" class="btn btn-secondary">Agregar reseña</button>
  </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
