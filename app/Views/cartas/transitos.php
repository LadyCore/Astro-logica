<?php
/** @var array<string, mixed> $form */
/** @var bool $submitted */
/** @var string|null $chartSvg */
/** @var string|null $error */
/** @var bool $apiReady */
$mode = 'transitos';
?>
<section class="feature-page">
  <header class="feature-hero">
    <p class="hoy-date">FREEASTROAPI · TRANSIT CHART</p>
    <h1 class="hoy-title">Tránsitos</h1>
    <p class="hoy-sub">El cielo de ahora sobre tu carta natal: aspectos activos y bi-rueda en vivo.</p>
  </header>

  <?php if (!$apiReady): ?>
    <p class="feature-banner is-warn">Falta la API key. Agregá <code>FREE_ASTRO_API_KEY</code> en <code>.env</code>.</p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="feature-banner is-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <div class="feature-layout<?= $chartSvg ? ' has-result' : '' ?>">
    <form class="astro-form" method="post" action="/transitos">
      <h2>Natal + momento</h2>
      <?php require dirname(__DIR__) . '/partials/birth-fields.php'; ?>
      <button type="submit" class="astro-submit">Calcular tránsitos</button>
    </form>

    <div class="feature-result">
      <?php if ($chartSvg): ?>
        <div class="feature-chart ivory-panel" aria-label="Carta de tránsitos">
          <?= $chartSvg ?>
        </div>
      <?php else: ?>
        <p class="feature-empty">Elegí el momento y el lugar actual para ver la bi-rueda de tránsitos.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
