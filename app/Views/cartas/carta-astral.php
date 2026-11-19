<?php
/** @var array<string, mixed> $form */
/** @var bool $submitted */
/** @var string|null $chartSvg */
/** @var list<\App\Models\PlanetPosition> $primaryBodies */
/** @var list<\App\Models\PlanetPosition> $secondaryBodies */
/** @var string|null $error */
/** @var bool $apiReady */
$mode = 'carta-astral';
$primaryBodies = $primaryBodies ?? [];
$secondaryBodies = $secondaryBodies ?? [];
?>
<section class="feature-page">
  <header class="feature-hero">
    <p class="hoy-date">MAPA NATAL</p>
    <h1 class="hoy-title">Carta Astral</h1>
    <p class="hoy-sub">Tu mapa natal calculado con precisión a partir de fecha, hora y lugar de nacimiento.</p>
  </header>

  <?php if (!$apiReady): ?>
    <p class="feature-banner is-warn">Todavía no está configurada la conexión al cielo en vivo.</p>
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
        <p class="feature-empty">Completá tus datos para ver la rueda natal.</p>
      <?php endif; ?>
    </div>
  </div>

  <?php
  $sectionTitle = 'Interpretación de tu carta';
  require dirname(__DIR__) . '/partials/planet-interpretations.php';
  ?>
</section>
