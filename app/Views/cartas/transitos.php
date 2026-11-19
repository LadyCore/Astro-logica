<?php
/** @var array<string, mixed> $form */
/** @var bool $submitted */
/** @var string|null $chartSvg */
/** @var list<\App\Models\PlanetPosition> $primaryBodies */
/** @var list<\App\Models\PlanetPosition> $secondaryBodies */
/** @var string|null $error */
/** @var bool $apiReady */
/** @var callable(string, array=): string $t */
/** @var string $lang */
$mode = 'transitos';
$primaryBodies = $primaryBodies ?? [];
$secondaryBodies = $secondaryBodies ?? [];
?>
<section class="feature-page">
  <header class="feature-hero">
    <p class="hoy-date"><?= htmlspecialchars($t('transit.eyebrow'), ENT_QUOTES, 'UTF-8') ?></p>
    <h1 class="hoy-title"><?= htmlspecialchars($t('transit.title'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="hoy-sub"><?= htmlspecialchars($t('transit.sub'), ENT_QUOTES, 'UTF-8') ?></p>
  </header>

  <?php if (!$apiReady): ?>
    <p class="feature-banner is-warn"><?= htmlspecialchars($t('api.missing'), ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="feature-banner is-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <div class="feature-layout<?= $chartSvg ? ' has-result' : '' ?>">
    <form class="astro-form" method="post" action="/transitos">
      <h2><?= htmlspecialchars($t('transit.form_title'), ENT_QUOTES, 'UTF-8') ?></h2>
      <?php require dirname(__DIR__) . '/partials/birth-fields.php'; ?>
      <button type="submit" class="astro-submit"><?= htmlspecialchars($t('transit.submit'), ENT_QUOTES, 'UTF-8') ?></button>
    </form>

    <div class="feature-result">
      <?php if ($chartSvg): ?>
        <div class="feature-chart ivory-panel" aria-label="<?= htmlspecialchars($t('transit.title'), ENT_QUOTES, 'UTF-8') ?>">
          <?= $chartSvg ?>
        </div>
      <?php else: ?>
        <p class="feature-empty"><?= htmlspecialchars($t('transit.empty'), ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
    </div>
  </div>

  <?php
  $sectionTitle = $t('transit.interp_title');
  require dirname(__DIR__) . '/partials/planet-interpretations.php';
  ?>
</section>
