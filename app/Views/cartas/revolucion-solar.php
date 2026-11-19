<?php
/** @var array<string, mixed> $form */
/** @var bool $submitted */
/** @var string|null $chartSvg */
/** @var string|null $error */
/** @var bool $apiReady */
$mode = 'revolucion-solar';
?>
<section class="feature-page">
  <header class="feature-hero">
    <p class="hoy-date">FREEASTROAPI · SOLAR RETURN</p>
    <h1 class="hoy-title">Revolución Solar</h1>
    <p class="hoy-sub">La carta del año: el instante exacto en que el Sol vuelve a tu longitud natal.</p>
  </header>

  <?php if (!$apiReady): ?>
    <p class="feature-banner is-warn">Falta la API key. Agregá <code>FREE_ASTRO_API_KEY</code> en <code>.env</code>.</p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="feature-banner is-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <div class="feature-layout<?= $chartSvg ? ' has-result' : '' ?>">
    <form class="astro-form" method="post" action="/revolucion-solar">
      <h2>Datos de nacimiento</h2>
      <?php require dirname(__DIR__) . '/partials/birth-fields.php'; ?>
      <button type="submit" class="astro-submit">Calcular revolución solar</button>
    </form>

    <div class="feature-result">
      <?php if ($chartSvg): ?>
        <div class="feature-chart ivory-panel" aria-label="Revolución solar">
          <?= $chartSvg ?>
        </div>
      <?php else: ?>
        <p class="feature-empty">Indicá el año de retorno para ver la rueda de tu revolución solar.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
