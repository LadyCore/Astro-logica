<?php
/** @var array<string, mixed> $form */
/** @var bool $submitted */
/** @var string|null $chartSvg */
/** @var \App\Models\NatalChart|null $natal */
/** @var string|null $error */
/** @var bool $apiReady */
$mode = 'carta-astral';
?>
<section class="feature-page">
  <header class="feature-hero">
    <p class="hoy-date">FREEASTROAPI · BIRTH CHART</p>
    <h1 class="hoy-title">Carta Astral</h1>
    <p class="hoy-sub">Tu mapa natal calculado con precisión a partir de fecha, hora y lugar de nacimiento.</p>
  </header>

  <?php if (!$apiReady): ?>
    <p class="feature-banner is-warn">Falta la API key. Agregá <code>FREE_ASTRO_API_KEY</code> en <code>.env</code>.</p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="feature-banner is-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <div class="feature-layout<?= $chartSvg ? ' has-result' : '' ?>">
    <form class="astro-form" method="post" action="/carta-astral">
      <h2>Datos de nacimiento</h2>
      <?php require dirname(__DIR__) . '/partials/birth-fields.php'; ?>
      <button type="submit" class="astro-submit">Calcular carta astral</button>
    </form>

    <div class="feature-result">
      <?php if ($chartSvg): ?>
        <div class="feature-chart ivory-panel" aria-label="Carta astral">
          <?= $chartSvg ?>
        </div>
      <?php elseif ($submitted && !$error): ?>
        <p class="feature-empty">Generando…</p>
      <?php else: ?>
        <p class="feature-empty">Completá tus datos para ver la rueda natal en estilo Ivory Ink.</p>
      <?php endif; ?>
    </div>
  </div>
</section>
