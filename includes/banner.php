<?php
declare(strict_types=1);

$bannerPath = __DIR__ . '/../assets/img/header-banner.png';
$bannerExists = file_exists($bannerPath);
?>
<div class="brand-banner">
  <?php if ($bannerExists): ?>
    <img
      src="assets/img/header-banner.png?v=<?= (int) filemtime($bannerPath) ?>"
      alt="NagoScale - Inmuebles por días"
      class="brand-banner-img"
    >
  <?php else: ?>
    <div class="brand-banner-fallback">
      <div class="brand-logo">
        <span class="brand-mark">N</span>
        <span class="brand-name">Nago<strong>Scale</strong></span>
      </div>
      <p class="brand-tagline">
        ESCALAMOS NEGOCIOS CON <strong>TECNOLOGÍA, PUBLICIDAD E INTELIGENCIA ARTIFICIAL</strong>
      </p>
      <p class="brand-hint">
        Sube tu imagen de banner a <code>assets/img/header-banner.png</code> para reemplazar este encabezado.
      </p>
    </div>
  <?php endif; ?>
</div>
