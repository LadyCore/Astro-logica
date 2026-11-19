<?php
/** @var array<string, mixed> $form */
/** @var bool $submitted */
/** @var string|null $chartSvg */
/** @var list<\App\Models\PlanetPosition> $primaryBodies */
/** @var list<\App\Models\PlanetPosition> $secondaryBodies */
/** @var string|null $error */
/** @var bool $apiReady */
$mode = 'revolucion-solar';
$primaryBodies = $primaryBodies ?? [];
$secondaryBodies = $secondaryBodies ?? [];
?>
<section class="feature-page">
  <header class="feature-hero">
    <p class="hoy-date">CARTA DEL AÑO</p>
    <h1 class="hoy-title">Revolución Solar</h1>
    <p class="hoy-sub">La carta del año: el instante exacto en que el Sol vuelve a tu longitud natal.</p>
  </header>

  <?php if (!$apiReady): ?>
    <p class="feature-banner is-warn">Todavía no está configurada la conexión al cielo en vivo.</p>
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

  <?php
  $sectionTitle = 'Interpretación de la revolución';
  require dirname(__DIR__) . '/partials/planet-interpretations.php';
  ?>
</section>
