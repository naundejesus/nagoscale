<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$userId = (int) ($_GET['u'] ?? 0);

if ($userId <= 0) {
    http_response_code(404);
    die('Página no encontrada.');
}

$stmt = $pdo->prepare('SELECT id, username FROM users WHERE id = ?');
$stmt->execute([$userId]);
$propietarioCuenta = $stmt->fetch();
if (!$propietarioCuenta) {
    http_response_code(404);
    die('Página no encontrada.');
}

// --- Parámetros de búsqueda y filtros ---
$destino = trim($_GET['destino'] ?? '');
$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';
$huespedes = (int) ($_GET['huespedes'] ?? 0);
$precioMin = $_GET['precio_min'] ?? '';
$precioMax = $_GET['precio_max'] ?? '';
$habitacionesMin = (int) ($_GET['habitaciones_min'] ?? 0);
$banosMin = (int) ($_GET['banos_min'] ?? 0);
$amenidadesSeleccionadas = array_intersect(
    (array) ($_GET['amenidades'] ?? []),
    array_keys(amenidades_disponibles())
);
$orden = $_GET['orden'] ?? 'relevancia';

$checkinValido = $checkin !== '' && DateTime::createFromFormat('Y-m-d', $checkin) !== false;
$checkoutValido = $checkout !== '' && DateTime::createFromFormat('Y-m-d', $checkout) !== false;

$where = ['a.user_id = ?'];
$params = [$userId];

if ($destino !== '') {
    $where[] = '(a.nombre LIKE ? OR a.ciudad LIKE ? OR a.zona LIKE ? OR a.direccion LIKE ?)';
    $like = '%' . $destino . '%';
    array_push($params, $like, $like, $like, $like);
}
if ($checkinValido && $checkoutValido && $checkout > $checkin) {
    $where[] = 'NOT EXISTS (
        SELECT 1 FROM reservas r
        WHERE r.apartamento_id = a.id AND r.fecha_inicio <= ? AND r.fecha_fin >= ?
    )';
    array_push($params, $checkout, $checkin);
}
if ($huespedes > 0) {
    $where[] = '(a.capacidad_huespedes IS NULL OR a.capacidad_huespedes >= ?)';
    $params[] = $huespedes;
}
if ($precioMin !== '' && is_numeric($precioMin)) {
    $where[] = '(a.precio_noche IS NULL OR a.precio_noche >= ?)';
    $params[] = $precioMin;
}
if ($precioMax !== '' && is_numeric($precioMax)) {
    $where[] = '(a.precio_noche IS NULL OR a.precio_noche <= ?)';
    $params[] = $precioMax;
}
if ($habitacionesMin > 0) {
    $where[] = 'a.habitaciones >= ?';
    $params[] = $habitacionesMin;
}
if ($banosMin > 0) {
    $where[] = 'a.banos >= ?';
    $params[] = $banosMin;
}
foreach ($amenidadesSeleccionadas as $amenidad) {
    $where[] = 'a.amenidades LIKE ?';
    $params[] = '%"' . $amenidad . '"%';
}

$ordenSql = 'a.nombre ASC';
if ($orden === 'precio_asc') $ordenSql = 'a.precio_noche IS NULL, a.precio_noche ASC';
if ($orden === 'precio_desc') $ordenSql = 'a.precio_noche IS NULL, a.precio_noche DESC';
if ($orden === 'calificacion') $ordenSql = 'promedio_calificacion DESC, total_resenas DESC';

$sql = "SELECT a.*,
            (SELECT archivo FROM apartamento_fotos f WHERE f.apartamento_id = a.id ORDER BY f.es_principal DESC, f.id ASC LIMIT 1) AS foto_portada,
            (SELECT COUNT(*) FROM apartamento_fotos f WHERE f.apartamento_id = a.id) AS total_fotos,
            (SELECT GROUP_CONCAT(t.archivo ORDER BY t.es_principal DESC, t.id ASC SEPARATOR '|') FROM (
                SELECT archivo, es_principal, id FROM apartamento_fotos WHERE apartamento_id = a.id ORDER BY es_principal DESC, id ASC LIMIT 4
            ) t) AS fotos_preview,
            (SELECT COALESCE(AVG(calificacion), 0) FROM resenas r WHERE r.apartamento_id = a.id) AS promedio_calificacion,
            (SELECT COUNT(*) FROM resenas r WHERE r.apartamento_id = a.id) AS total_resenas
        FROM apartamentos a
        WHERE " . implode(' AND ', $where);

$sql .= " ORDER BY $ordenSql";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$apartamentos = $stmt->fetchAll();

$queryStringBase = $_GET;
unset($queryStringBase['u']);

$pageTitle = 'Alojamientos disponibles';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e(brand_name()) ?></title>
<meta name="description" content="Encuentra y reserva apartamentos por días, seleccionados y verificados.">
<link rel="icon" type="image/png" href="assets/img/favicon.png">
<link rel="stylesheet" href="assets/css/style.css?v=<?= e(asset_version()) ?>">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<?php require __DIR__ . '/includes/brand_style.php'; ?>
</head>
<body class="ne-body">
<?php require __DIR__ . '/includes/ne_header.php'; ?>

<section class="ne-hero">
  <div class="ne-hero-inner">
    <h1>Encuentra el lugar perfecto para tu próxima escapada</h1>
    <p>Apartamentos y alojamientos seleccionados para que disfrutes tu destino.</p>
  </div>
</section>

<div class="container" style="padding-top:0;">
  <form class="ne-search" method="get">
    <input type="hidden" name="u" value="<?= (int) $userId ?>">
    <div class="ne-search-field">
      <label>Destino</label>
      <input type="text" name="destino" value="<?= e($destino) ?>" placeholder="¿A dónde quieres ir?">
    </div>
    <div class="ne-search-divider"></div>
    <div class="ne-search-field">
      <label>Llegada</label>
      <input type="date" name="checkin" value="<?= e($checkin) ?>">
    </div>
    <div class="ne-search-divider"></div>
    <div class="ne-search-field">
      <label>Salida</label>
      <input type="date" name="checkout" value="<?= e($checkout) ?>">
    </div>
    <div class="ne-search-divider"></div>
    <div class="ne-search-field">
      <label>Huéspedes</label>
      <input type="number" name="huespedes" min="1" value="<?= $huespedes ?: '' ?>" placeholder="¿Cuántas personas?">
    </div>
    <button type="submit" class="ne-search-btn">Buscar</button>
  </form>

  <div class="ne-results-header">
    <p class="ne-results-count"><strong><?= count($apartamentos) ?></strong> alojamiento<?= count($apartamentos) === 1 ? '' : 's' ?> encontrado<?= count($apartamentos) === 1 ? '' : 's' ?></p>
    <div style="display:flex; gap:10px; align-items:center;">
      <button type="button" class="btn btn-secondary ne-map-toggle" id="ne-map-toggle-btn">Ver mapa</button>
      <form method="get" id="orden-form" style="display:flex; align-items:center; gap:6px;">
        <?php foreach ($queryStringBase as $k => $v): if ($k === 'orden') continue; ?>
          <?php if (is_array($v)): foreach ($v as $vv): ?><input type="hidden" name="<?= e($k) ?>[]" value="<?= e($vv) ?>"><?php endforeach; else: ?>
          <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
          <?php endif; ?>
        <?php endforeach; ?>
        <input type="hidden" name="u" value="<?= (int) $userId ?>">
        <label style="font-size:13px; color:var(--text-secondary);">Ordenar por</label>
        <select name="orden" onchange="document.getElementById('orden-form').submit()">
          <option value="relevancia" <?= $orden === 'relevancia' ? 'selected' : '' ?>>Relevancia</option>
          <option value="precio_asc" <?= $orden === 'precio_asc' ? 'selected' : '' ?>>Precio: menor a mayor</option>
          <option value="precio_desc" <?= $orden === 'precio_desc' ? 'selected' : '' ?>>Precio: mayor a menor</option>
          <option value="calificacion" <?= $orden === 'calificacion' ? 'selected' : '' ?>>Mejor calificados</option>
        </select>
      </form>
    </div>
  </div>

  <div class="ne-results-layout con-mapa">
    <aside class="ne-filters" id="ne-filters">
      <form method="get">
        <input type="hidden" name="u" value="<?= (int) $userId ?>">
        <?php if ($destino): ?><input type="hidden" name="destino" value="<?= e($destino) ?>"><?php endif; ?>
        <?php if ($checkin): ?><input type="hidden" name="checkin" value="<?= e($checkin) ?>"><?php endif; ?>
        <?php if ($checkout): ?><input type="hidden" name="checkout" value="<?= e($checkout) ?>"><?php endif; ?>
        <?php if ($huespedes): ?><input type="hidden" name="huespedes" value="<?= $huespedes ?>"><?php endif; ?>

        <div class="ne-filter-group">
          <h3>Precio por noche</h3>
          <div class="ne-range-row">
            <input type="number" name="precio_min" placeholder="Mín" value="<?= e($precioMin) ?>">
            <input type="number" name="precio_max" placeholder="Máx" value="<?= e($precioMax) ?>">
          </div>
        </div>

        <div class="ne-filter-group">
          <h3>Habitaciones</h3>
          <select name="habitaciones_min">
            <option value="0">Cualquiera</option>
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <option value="<?= $i ?>" <?= $habitacionesMin === $i ? 'selected' : '' ?>><?= $i ?>+</option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="ne-filter-group">
          <h3>Baños</h3>
          <select name="banos_min">
            <option value="0">Cualquiera</option>
            <?php for ($i = 1; $i <= 4; $i++): ?>
              <option value="<?= $i ?>" <?= $banosMin === $i ? 'selected' : '' ?>><?= $i ?>+</option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="ne-filter-group">
          <h3>Servicios</h3>
          <?php foreach (amenidades_disponibles() as $key => $info): ?>
            <label class="ne-checkbox">
              <input type="checkbox" name="amenidades[]" value="<?= e($key) ?>" <?= in_array($key, $amenidadesSeleccionadas, true) ? 'checked' : '' ?>>
              <?= $info['icon'] ?> <?= e($info['label']) ?>
            </label>
          <?php endforeach; ?>
        </div>

        <div class="ne-filter-actions">
          <button type="submit" class="btn btn-primary btn-block">Aplicar filtros</button>
        </div>
      </form>
      <a href="?u=<?= (int) $userId ?>" class="btn-link">Restablecer filtros</a>
    </aside>

    <div>
      <?php if (!$apartamentos): ?>
        <div class="ne-empty">
          <h3>No encontramos alojamientos para estos criterios</h3>
          <p>Prueba cambiando las fechas, los filtros, o ampliando tu búsqueda.</p>
          <a href="?u=<?= (int) $userId ?>" class="btn btn-primary">Modificar búsqueda</a>
        </div>
      <?php else: ?>
        <div class="ne-grid">
          <?php foreach ($apartamentos as $a): ?>
            <?php $fotosPreview = $a['fotos_preview'] ? explode('|', $a['fotos_preview']) : []; ?>
            <div class="ne-card">
              <div class="ne-card-media">
                <?php if ($fotosPreview): ?>
                  <div class="ne-card-carrusel">
                    <?php foreach ($fotosPreview as $i => $foto): ?>
                      <img src="assets/uploads/apartamentos/<?= e($foto) ?>" alt="<?= e($a['nombre']) ?>" class="ne-card-img<?= $i === 0 ? ' activa' : '' ?>" loading="lazy">
                    <?php endforeach; ?>
                  </div>
                  <?php if (count($fotosPreview) > 1): ?>
                    <button type="button" class="ne-card-nav ne-card-nav-prev" onclick="neCardFoto(this, -1)" aria-label="Foto anterior">‹</button>
                    <button type="button" class="ne-card-nav ne-card-nav-next" onclick="neCardFoto(this, 1)" aria-label="Foto siguiente">›</button>
                    <div class="ne-card-dots">
                      <?php foreach ($fotosPreview as $i => $foto): ?>
                        <span class="ne-card-dot<?= $i === 0 ? ' activo' : '' ?>"></span>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                <?php else: ?>
                  <div class="ne-card-img-placeholder"></div>
                <?php endif; ?>
                <button type="button" class="ne-fav-btn" data-fav-id="<?= (int) $a['id'] ?>" onclick="neToggleFav(this, <?= (int) $a['id'] ?>)">♥</button>
                <?php if ($a['total_fotos'] > 1): ?>
                  <span class="ne-card-photocount">📷 <?= (int) $a['total_fotos'] ?></span>
                <?php endif; ?>
              </div>
              <div class="ne-card-body">
                <span class="ne-card-title"><?= e($a['nombre']) ?></span>
                <?php $ubicacion = trim(implode(', ', array_filter([$a['zona'], $a['ciudad']]))); ?>
                <span class="ne-card-loc">📍 <?= e($ubicacion ?: ($a['direccion'] ?? 'Ubicación disponible al reservar')) ?></span>
                <?php if ($a['total_resenas'] > 0): ?>
                  <span class="ne-card-rating"><span class="ne-stars"><?= estrellas_html((float) $a['promedio_calificacion']) ?></span> <?= number_format((float) $a['promedio_calificacion'], 1) ?> (<?= (int) $a['total_resenas'] ?>)</span>
                <?php endif; ?>
                <span class="ne-card-meta">
                  <?php
                    $meta = [];
                    if ($a['habitaciones'] !== null) $meta[] = $a['habitaciones'] . ' hab.';
                    if ($a['banos'] !== null) $meta[] = $a['banos'] . ' baños';
                    if ($a['capacidad_huespedes'] !== null) $meta[] = $a['capacidad_huespedes'] . ' huéspedes';
                    echo e(implode(' · ', $meta));
                  ?>
                </span>
                <?php if ($a['precio_noche'] !== null): ?>
                  <div class="ne-card-price">
                    <strong><?= formatCOP($a['precio_noche']) ?></strong>
                    <span>por noche</span>
                  </div>
                <?php endif; ?>
              </div>
              <a href="reservar_form.php?apartamento_id=<?= (int) $a['id'] ?>" class="btn btn-outline btn-block ne-card-cta">Ver alojamiento</a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="ne-map-wrap" id="ne-map-wrap">
      <div id="ne-map"></div>
    </div>
  </div>
</div>

<div class="ne-toast" id="ne-toast"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/favoritos.js"></script>
<script>
  var mapToggleBtn = document.getElementById('ne-map-toggle-btn');
  var mapWrap = document.getElementById('ne-map-wrap');
  var filtersEl = document.getElementById('ne-filters');
  if (mapToggleBtn) {
    mapToggleBtn.addEventListener('click', function () {
      mapWrap.classList.toggle('abierto');
      mapToggleBtn.textContent = mapWrap.classList.contains('abierto') ? 'Ocultar mapa' : 'Ver mapa';
    });
  }

  var puntos = <?= json_encode(array_values(array_filter(array_map(function ($a) {
      return ($a['latitud'] !== null && $a['longitud'] !== null) ? [
          'lat' => (float) $a['latitud'],
          'lng' => (float) $a['longitud'],
          'nombre' => $a['nombre'],
          'precio' => $a['precio_noche'] !== null ? formatCOP($a['precio_noche']) : null,
          'url' => 'reservar_form.php?apartamento_id=' . (int) $a['id'],
      ] : null;
  }, $apartamentos)))) ?>;

  if (puntos.length) {
    var mapa = L.map('ne-map').setView([puntos[0].lat, puntos[0].lng], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap'
    }).addTo(mapa);
    var grupo = [];
    puntos.forEach(function (p) {
      var popup = '<strong>' + p.nombre + '</strong>' + (p.precio ? '<br>' + p.precio + ' / noche' : '') + '<br><a href="' + p.url + '">Ver alojamiento</a>';
      var marcador = L.marker([p.lat, p.lng]).addTo(mapa).bindPopup(popup);
      grupo.push(marcador);
    });
    if (grupo.length > 1) {
      mapa.fitBounds(L.featureGroup(grupo).getBounds().pad(0.2));
    }
  } else {
    document.getElementById('ne-map-wrap').innerHTML = '<div class="ne-empty" style="padding:24px;"><p>Ninguno de estos alojamientos tiene ubicación marcada en el mapa todavía.</p></div>';
  }
</script>

<?php require __DIR__ . '/includes/ne_footer.php'; ?>
