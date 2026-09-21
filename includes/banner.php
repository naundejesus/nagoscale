<?php
declare(strict_types=1);

$bannerPath = __DIR__ . '/../assets/img/header-banner.png';
$bannerExists = file_exists($bannerPath);
?>
<div class="brand-banner">
  <?php if ($bannerExists): ?>
    <img
      src="assets/img/header-banner.png?v=<?= (int) filemtime($bannerPath) ?>"
      alt="<?= e(brand_name()) ?> - <?= e(brand_tagline()) ?>"
      class="brand-banner-img"
    >
  <?php else: ?>
    <div class="brand-banner-fallback">
      <div class="brand-logo">
        <span class="brand-mark"><?= e(mb_substr(brand_name(), 0, 1)) ?></span>
        <span class="brand-name"><?= e(brand_name()) ?></span>
      </div>
      <p class="brand-tagline">
        <?= e(brand_tagline()) ?>
      </p>
      <p class="brand-hint">
        Sube tu imagen de banner a <code>assets/img/header-banner.png</code> para reemplazar este encabezado.
      </p>
    </div>
  <?php endif; ?>
</div>
