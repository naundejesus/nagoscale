<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$apartamentoId = (int) ($_GET['apartamento_id'] ?? ($_POST['apartamento_id'] ?? 0));

$stmt = $pdo->prepare('SELECT * FROM apartamentos WHERE id = ?');
$stmt->execute([$apartamentoId]);
$apartamento = $stmt->fetch();
if (!$apartamento) {
    http_response_code(404);
    die('Apartamento no encontrado.');
}

$stmt = $pdo->prepare('SELECT id, username FROM users WHERE id = ?');
$stmt->execute([$apartamento['user_id']]);
$propietarioCuenta = $stmt->fetch();

$stmt = $pdo->prepare('SELECT archivo FROM apartamento_fotos WHERE apartamento_id = ? ORDER BY es_principal DESC, id ASC LIMIT 20');
$stmt->execute([$apartamentoId]);
$fotos = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare('SELECT nequi_numero, bancolombia_tipo_cuenta, bancolombia_numero, bancolombia_titular FROM users WHERE id = ?');
$stmt->execute([$apartamento['user_id']]);
$datosPago = $stmt->fetch();

$stmt = $pdo->prepare('SELECT * FROM resenas WHERE apartamento_id = ? ORDER BY created_at DESC');
$stmt->execute([$apartamentoId]);
$resenas = $stmt->fetchAll();
$promedioCalificacion = $resenas ? array_sum(array_column($resenas, 'calificacion')) / count($resenas) : 0;

$amenidadesApartamento = decodificar_amenidades($apartamento['amenidades'] ?? null);
$catalogoAmenidades = amenidades_disponibles();

$error = '';
$enviado = false;
$solicitudId = null;

$solicitud = [
    'nombre_cliente' => '',
    'telefono' => '',
    'correo' => '',
    'mensaje' => '',
    'fecha_inicio' => $_GET['fecha_inicio'] ?? '',
    'fecha_fin' => $_GET['fecha_fin'] ?? '',
    'huespedes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $solicitud['nombre_cliente'] = trim($_POST['nombre_cliente'] ?? '');
    $solicitud['telefono'] = trim($_POST['telefono'] ?? '');
    $solicitud['correo'] = trim($_POST['correo'] ?? '');
    $solicitud['mensaje'] = trim($_POST['mensaje'] ?? '');
    $solicitud['fecha_inicio'] = $_POST['fecha_inicio'] ?? '';
    $solicitud['fecha_fin'] = $_POST['fecha_fin'] ?? '';
    $solicitud['huespedes'] = $_POST['huespedes'] ?? '';
    $huespedesNum = $solicitud['huespedes'] !== '' ? (int) $solicitud['huespedes'] : null;

    if ($solicitud['nombre_cliente'] === '') {
        $error = 'Escribe tu nombre completo.';
    } elseif ($solicitud['telefono'] === '') {
        $error = 'Escribe un teléfono de contacto.';
    } elseif ($solicitud['correo'] !== '' && !filter_var($solicitud['correo'], FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } elseif (DateTime::createFromFormat('Y-m-d', $solicitud['fecha_inicio']) === false) {
        $error = 'La fecha de inicio no es válida.';
    } elseif (DateTime::createFromFormat('Y-m-d', $solicitud['fecha_fin']) === false) {
        $error = 'La fecha de fin no es válida.';
    } elseif ($solicitud['fecha_fin'] < $solicitud['fecha_inicio']) {
        $error = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM reservas WHERE apartamento_id = ? AND fecha_inicio <= ? AND fecha_fin >= ?'
        );
        $stmt->execute([$apartamentoId, $solicitud['fecha_fin'], $solicitud['fecha_inicio']]);
        if ((int) $stmt->fetchColumn() > 0) {
            $error = 'Esas fechas ya no están disponibles para este apartamento. Elige otras.';
        }
    }

    if (!$error) {
        $valorEstimado = calcular_valor_estadia(
            $apartamento['precio_noche'] !== null ? (float) $apartamento['precio_noche'] : null,
            $apartamento['precio_fin_semana'] !== null ? (float) $apartamento['precio_fin_semana'] : null,
            $solicitud['fecha_inicio'],
            $solicitud['fecha_fin']
        );

        $stmt = $pdo->prepare(
            'INSERT INTO solicitudes (apartamento_id, nombre_cliente, telefono, correo, mensaje, fecha_inicio, fecha_fin, huespedes, valor_estimado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $apartamentoId,
            $solicitud['nombre_cliente'],
            $solicitud['telefono'],
            $solicitud['correo'] ?: null,
            $solicitud['mensaje'] ?: null,
            $solicitud['fecha_inicio'],
            $solicitud['fecha_fin'],
            $huespedesNum,
            $valorEstimado,
        ]);
        $solicitudId = (int) $pdo->lastInsertId();

        $stmt = $pdo->prepare('SELECT email FROM users WHERE id = ?');
        $stmt->execute([$apartamento['user_id']]);
        $emailPropietario = (string) $stmt->fetchColumn();

        enviar_notificacion_solicitud($emailPropietario, [
            'apartamento_nombre' => $apartamento['nombre'],
            'nombre_cliente' => $solicitud['nombre_cliente'],
            'telefono' => $solicitud['telefono'],
            'correo' => $solicitud['correo'],
            'fecha_inicio' => $solicitud['fecha_inicio'],
            'fecha_fin' => $solicitud['fecha_fin'],
            'mensaje' => $solicitud['mensaje'],
        ]);

        header('Location: reservar_confirmacion.php?id=' . $solicitudId);
        exit;
    }
}

$ubicacion = trim(implode(', ', array_filter([$apartamento['zona'], $apartamento['ciudad']])));
$metaPartes = [];
if ($apartamento['capacidad_huespedes'] !== null) $metaPartes[] = $apartamento['capacidad_huespedes'] . ' huéspedes';
if ($apartamento['habitaciones'] !== null) $metaPartes[] = $apartamento['habitaciones'] . ' habitaciones';
if ($apartamento['banos'] !== null) $metaPartes[] = $apartamento['banos'] . ' baños';
if ($apartamento['cocinas'] !== null) $metaPartes[] = $apartamento['cocinas'] . ' cocina(s)';
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($apartamento['nombre']) ?> · NagoScale</title>
<meta name="description" content="<?= e(mb_substr((string) ($apartamento['descripcion'] ?? $apartamento['nombre']), 0, 155)) ?>">
<link rel="icon" type="image/png" href="assets/img/favicon.png">
<link rel="stylesheet" href="assets/css/style.css?v=<?= e(asset_version()) ?>">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body class="ne-body">
<?php require __DIR__ . '/includes/ne_header.php'; ?>

<div class="container">

  <div class="ne-listing-head">
    <div>
      <h1 class="ne-listing-title"><?= e($apartamento['nombre']) ?></h1>
      <p class="ne-listing-loc">📍 <?= e($ubicacion ?: ($apartamento['direccion'] ?? '')) ?></p>
    </div>
    <?php if ($resenas): ?>
      <div class="ne-listing-rating">
        <span class="ne-stars"><?= estrellas_html($promedioCalificacion) ?></span>
        <?= number_format($promedioCalificacion, 1) ?> · <?= count($resenas) ?> reseña<?= count($resenas) === 1 ? '' : 's' ?>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($fotos): ?>
    <div class="ne-gallery">
      <div class="ne-gallery-main">
        <img src="assets/uploads/apartamentos/<?= e($fotos[0]) ?>" alt="<?= e($apartamento['nombre']) ?>" id="ne-foto-principal">
      </div>
      <div class="ne-gallery-side">
        <?php for ($i = 1; $i <= 4; $i++): ?>
          <?php if (isset($fotos[$i])): ?>
            <div class="<?= ($i === 4 && count($fotos) > 5) ? 'ne-gallery-more' : '' ?>" data-mas="+<?= count($fotos) - 5 ?> fotos">
              <img src="assets/uploads/apartamentos/<?= e($fotos[$i]) ?>" alt="">
            </div>
          <?php else: ?>
            <div></div>
          <?php endif; ?>
        <?php endfor; ?>
      </div>
      <?php if (count($fotos) > 1): ?>
        <button type="button" class="ne-gallery-btn" onclick="document.getElementById('ne-lightbox').style.display='flex'">Ver todas las fotos (<?= count($fotos) ?>)</button>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="ne-card-img-placeholder" style="border-radius:var(--radius-lg); height:320px;"></div>
  <?php endif; ?>

  <div class="ne-listing-layout">
    <div class="ne-listing-main">

      <div class="ne-listing-meta"><?= e(implode(' · ', $metaPartes)) ?></div>

      <?php if ($apartamento['descripcion']): ?>
        <div class="ne-section">
          <h2>Descripción</h2>
          <p class="ne-description"><?= e($apartamento['descripcion']) ?></p>
        </div>
      <?php endif; ?>

      <?php if ($amenidadesApartamento): ?>
        <div class="ne-section">
          <h2>Servicios</h2>
          <div class="ne-amenities">
            <?php foreach ($amenidadesApartamento as $key): ?>
              <?php if (isset($catalogoAmenidades[$key])): ?>
                <div class="ne-amenity"><span class="ne-amenity-icon"><?= $catalogoAmenidades[$key]['icon'] ?></span> <?= e($catalogoAmenidades[$key]['label']) ?></div>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($apartamento['latitud'] !== null && $apartamento['longitud'] !== null): ?>
        <div class="ne-section">
          <h2>Ubicación</h2>
          <div style="height:280px; border-radius:var(--radius-md); overflow:hidden; border:1px solid var(--border);" id="ne-map-listing"></div>
        </div>
      <?php endif; ?>

      <div class="ne-section" id="ne-reviews">
        <h2>Reseñas <?= $resenas ? '(' . count($resenas) . ')' : '' ?></h2>
        <?php if (!$resenas): ?>
          <p style="color:var(--text-secondary); font-size:14px;">Este apartamento aún no tiene reseñas.</p>
        <?php else: ?>
          <?php foreach ($resenas as $r): ?>
            <div class="ne-review">
              <div class="ne-review-head">
                <span class="ne-review-name"><?= e($r['nombre_cliente']) ?></span>
                <span class="ne-stars"><?= estrellas_html((float) $r['calificacion']) ?></span>
              </div>
              <?php if ($r['comentario']): ?><p class="ne-review-comment"><?= e($r['comentario']) ?></p><?php endif; ?>
              <span class="ne-review-date"><?= e((new DateTime($r['created_at']))->format('d/m/Y')) ?></span>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="ne-section" id="reservar">
        <h2>Solicitar reserva</h2>

        <?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>

        <div class="ne-steps" id="ne-steps">
          <div class="ne-step activo" data-step="1"><span class="ne-step-num">1</span> Fechas</div>
          <div class="ne-step-sep"></div>
          <div class="ne-step" data-step="2"><span class="ne-step-num">2</span> Tus datos</div>
          <div class="ne-step-sep"></div>
          <div class="ne-step" data-step="3"><span class="ne-step-num">3</span> Confirmar</div>
        </div>

        <form class="form-card" method="post" style="max-width:560px;" id="ne-form-reserva">
          <?= csrf_field() ?>
          <input type="hidden" name="apartamento_id" value="<?= (int) $apartamentoId ?>">

          <div class="ne-step-panel activo" data-panel="1">
            <label>Fecha de inicio (check-in)
              <input type="date" name="fecha_inicio" id="fecha_inicio" value="<?= e($solicitud['fecha_inicio']) ?>" required>
            </label>
            <label>Fecha de fin (check-out)
              <input type="date" name="fecha_fin" id="fecha_fin" value="<?= e($solicitud['fecha_fin']) ?>" required>
            </label>

            <div class="calendario-wrap">
              <span class="calendario-titulo">Disponibilidad</span>
              <div id="calendario" data-apartamento-id="<?= (int) $apartamentoId ?>"></div>
            </div>

            <label>Número de huéspedes
              <input type="number" name="huespedes" min="1" <?= $apartamento['capacidad_huespedes'] !== null ? 'max="' . (int) $apartamento['capacidad_huespedes'] . '"' : '' ?> value="<?= e($solicitud['huespedes']) ?>">
            </label>

            <div id="precio-box" class="precio-box" hidden>
              <div class="precio-total">
                <span>Valor total estimado</span>
                <strong id="precio-total-valor">$ 0</strong>
              </div>
              <div class="precio-pago">
                <p>Para apartar estas fechas, abona el <strong>50%</strong>: <strong id="precio-50-valor">$ 0</strong></p>
                <?php if ($datosPago && ($datosPago['nequi_numero'] || $datosPago['bancolombia_numero'])): ?>
                  <ul class="precio-cuentas">
                    <?php if ($datosPago['nequi_numero']): ?>
                      <li><strong>Nequi:</strong> <?= e($datosPago['nequi_numero']) ?></li>
                    <?php endif; ?>
                    <?php if ($datosPago['bancolombia_numero']): ?>
                      <li>
                        <strong>Bancolombia</strong>
                        (<?= e($datosPago['bancolombia_tipo_cuenta'] ?? '') ?>):
                        <?= e($datosPago['bancolombia_numero']) ?>
                        <?php if ($datosPago['bancolombia_titular']): ?> — <?= e($datosPago['bancolombia_titular']) ?><?php endif; ?>
                      </li>
                    <?php endif; ?>
                  </ul>
                  <p class="cal-hint">Envía tu comprobante junto con la solicitud para agilizar la confirmación.</p>
                <?php endif; ?>
              </div>
            </div>

            <button type="button" class="btn btn-primary" onclick="neIrPaso(2)">Continuar</button>
          </div>

          <div class="ne-step-panel" data-panel="2">
            <label>Nombre completo
              <input type="text" name="nombre_cliente" value="<?= e($solicitud['nombre_cliente']) ?>" required>
            </label>
            <label>Teléfono / WhatsApp
              <input type="tel" name="telefono" value="<?= e($solicitud['telefono']) ?>" required>
            </label>
            <label>Correo electrónico (opcional)
              <input type="email" name="correo" value="<?= e($solicitud['correo']) ?>">
            </label>
            <label>Mensaje (opcional)
              <input type="text" name="mensaje" maxlength="500" value="<?= e($solicitud['mensaje']) ?>" placeholder="Preguntas, hora de llegada, etc.">
            </label>
            <div style="display:flex; gap:10px;">
              <button type="button" class="btn btn-secondary" onclick="neIrPaso(1)">Atrás</button>
              <button type="button" class="btn btn-primary" onclick="neIrPaso(3)">Continuar</button>
            </div>
          </div>

          <div class="ne-step-panel" data-panel="3">
            <div class="ne-price-breakdown" id="ne-resumen">
              <div class="fila"><span>Alojamiento</span><span id="ne-resumen-nombre"><?= e($apartamento['nombre']) ?></span></div>
              <div class="fila"><span>Fechas</span><span id="ne-resumen-fechas">—</span></div>
              <div class="fila"><span>Huéspedes</span><span id="ne-resumen-huespedes">—</span></div>
              <div class="fila total"><span>Valor estimado</span><span id="ne-resumen-total">—</span></div>
            </div>
            <div style="display:flex; gap:10px;">
              <button type="button" class="btn btn-secondary" onclick="neIrPaso(2)">Atrás</button>
              <button type="submit" class="btn btn-primary btn-block">Enviar solicitud</button>
            </div>
          </div>
        </form>
      </div>

    </div>

    <aside class="ne-booking-card">
      <?php if ($apartamento['precio_noche'] !== null): ?>
        <div class="ne-booking-price"><?= formatCOP($apartamento['precio_noche']) ?> <span>/ noche</span></div>
      <?php endif; ?>
      <?php if ($resenas): ?>
        <div style="font-size:13px; color:var(--text-secondary);"><span class="ne-stars"><?= estrellas_html($promedioCalificacion) ?></span> <?= number_format($promedioCalificacion, 1) ?> (<?= count($resenas) ?>)</div>
      <?php endif; ?>
      <a href="#reservar" class="btn btn-primary btn-block">Reservar ahora</a>
      <p style="font-size:12px; color:var(--text-secondary); text-align:center;">No se te cobrará todavía</p>
    </aside>
  </div>
</div>

<?php if ($fotos): ?>
<div id="ne-lightbox" style="display:none; position:fixed; inset:0; background:rgba(19,30,65,0.92); z-index:80; align-items:center; justify-content:center; padding:20px;" onclick="if(event.target===this) this.style.display='none'">
  <div style="max-width:900px; width:100%;">
    <button type="button" onclick="document.getElementById('ne-lightbox').style.display='none'" style="background:#fff; border:none; border-radius:8px; padding:8px 14px; margin-bottom:10px; cursor:pointer; font-weight:600;">Cerrar ✕</button>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(220px,1fr)); gap:10px; max-height:80vh; overflow-y:auto;">
      <?php foreach ($fotos as $archivo): ?>
        <img src="assets/uploads/apartamentos/<?= e($archivo) ?>" style="width:100%; border-radius:8px;">
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($apartamento['precio_noche'] !== null): ?>
<div class="ne-mobile-book-bar">
  <span class="precio"><?= formatCOP($apartamento['precio_noche']) ?><span>/ noche</span></span>
  <a href="#reservar" class="btn btn-primary">Reservar</a>
</div>
<?php endif; ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="assets/js/calendario.js"></script>
<script>
  function neIrPaso(paso) {
    document.querySelectorAll('.ne-step-panel').forEach(function (p) {
      p.classList.toggle('activo', p.getAttribute('data-panel') === String(paso));
    });
    document.querySelectorAll('.ne-step').forEach(function (s) {
      var n = parseInt(s.getAttribute('data-step'), 10);
      s.classList.toggle('activo', n === paso);
      s.classList.toggle('completo', n < paso);
    });
    if (paso === 3) {
      actualizarResumen();
    }
  }

  function formatCOP(n) {
    return '$ ' + Math.round(n).toLocaleString('es-CO');
  }

  function actualizarPrecio() {
    var inicio = document.getElementById('fecha_inicio');
    var fin = document.getElementById('fecha_fin');
    var caja = document.getElementById('precio-box');
    if (!inicio.value || !fin.value || fin.value <= inicio.value) {
      caja.hidden = true;
      return;
    }
    var url = 'calcular_precio.php?apartamento_id=<?= (int) $apartamentoId ?>'
      + '&fecha_inicio=' + encodeURIComponent(inicio.value)
      + '&fecha_fin=' + encodeURIComponent(fin.value);
    fetch(url)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.tiene_precio) { caja.hidden = true; return; }
        document.getElementById('precio-total-valor').textContent = formatCOP(data.valor_total);
        document.getElementById('precio-50-valor').textContent = formatCOP(data.valor_50);
        caja.hidden = false;
      })
      .catch(function () { caja.hidden = true; });
  }

  function actualizarResumen() {
    var inicio = document.getElementById('fecha_inicio').value;
    var fin = document.getElementById('fecha_fin').value;
    var huespedes = document.querySelector('[name="huespedes"]').value;
    document.getElementById('ne-resumen-fechas').textContent = (inicio || '—') + ' → ' + (fin || '—');
    document.getElementById('ne-resumen-huespedes').textContent = huespedes ? huespedes : 'No especificado';
    var totalTexto = document.getElementById('precio-total-valor') ? document.getElementById('precio-total-valor').textContent : '—';
    document.getElementById('ne-resumen-total').textContent = document.getElementById('precio-box').hidden ? 'A confirmar' : totalTexto;
  }

  document.getElementById('fecha_inicio').addEventListener('change', actualizarPrecio);
  document.getElementById('fecha_fin').addEventListener('change', actualizarPrecio);
  actualizarPrecio();

  <?php if ($apartamento['latitud'] !== null && $apartamento['longitud'] !== null): ?>
  var mapaListing = L.map('ne-map-listing').setView([<?= (float) $apartamento['latitud'] ?>, <?= (float) $apartamento['longitud'] ?>], 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(mapaListing);
  L.marker([<?= (float) $apartamento['latitud'] ?>, <?= (float) $apartamento['longitud'] ?>]).addTo(mapaListing);
  <?php endif; ?>
</script>

<?php require __DIR__ . '/includes/ne_footer.php'; ?>
