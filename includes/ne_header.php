<?php
declare(strict_types=1);
/** @var array $propietarioCuenta opcional, para el link "volver a todos los alojamientos" */
?>
<header class="ne-header">
  <div class="ne-header-inner">
    <a href="<?= isset($propietarioCuenta) ? 'reservar.php?u=' . (int) $propietarioCuenta['id'] : 'javascript:history.back()' ?>" class="ne-logo">
      <span class="brand-mark">N</span>
      Nago<span>Scale</span>
    </a>
    <a href="alojamientos.php" class="btn btn-primary ne-header-cta">Ver todos los alojamientos disponibles</a>
  </div>
</header>
