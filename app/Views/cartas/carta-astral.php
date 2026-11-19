<?php
/** @var array<string, mixed> $form */
/** @var bool $submitted */
/** @var string|null $chartSvg */
/** @var list<\App\Models\PlanetPosition> $primaryBodies */
/** @var list<\App\Models\PlanetPosition> $secondaryBodies */
/** @var string|null $error */
/** @var bool $apiReady */
/** @var bool $pdfReady */
/** @var bool $canDownloadPdf */
/** @var callable(string, array=): string $t */
/** @var string $lang */
$mode = 'carta-astral';
$primaryBodies = $primaryBodies ?? [];
$secondaryBodies = $secondaryBodies ?? [];
$pdfReady = $pdfReady ?? false;
$canDownloadPdf = $canDownloadPdf ?? false;
?>
<section class="feature-page">
  <header class="feature-hero">
    <p class="hoy-date"><?= htmlspecialchars($t('carta.eyebrow'), ENT_QUOTES, 'UTF-8') ?></p>
    <h1 class="hoy-title"><?= htmlspecialchars($t('carta.title'), ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="hoy-sub"><?= htmlspecialchars($t('carta.sub'), ENT_QUOTES, 'UTF-8') ?></p>
  </header>

  <?php if (!$apiReady): ?>
    <p class="feature-banner is-warn"><?= htmlspecialchars($t('api.missing'), ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="feature-banner is-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <div class="feature-layout<?= $chartSvg ? ' has-result' : '' ?>">
    <form class="astro-form" method="post" action="/carta-astral" id="carta-astral-form">
      <h2><?= htmlspecialchars($t('carta.form_title'), ENT_QUOTES, 'UTF-8') ?></h2>
      <?php require dirname(__DIR__) . '/partials/birth-fields.php'; ?>
      <button type="submit" class="astro-submit"><?= htmlspecialchars($t('carta.submit'), ENT_QUOTES, 'UTF-8') ?></button>
      <?php if ($pdfReady): ?>
        <button
          type="submit"
          class="astro-submit astro-submit-secondary"
          formaction="/carta-astral/pdf"
          formmethod="post"
          <?= $canDownloadPdf ? '' : 'disabled title="' . htmlspecialchars($t('carta.pdf_need_chart'), ENT_QUOTES, 'UTF-8') . '"' ?>
        ><?= htmlspecialchars($t('carta.pdf_download'), ENT_QUOTES, 'UTF-8') ?></button>
      <?php else: ?>
        <p class="form-hint"><?= htmlspecialchars($t('carta.pdf_missing'), ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
    </form>

    <div class="feature-result">
      <?php if ($chartSvg): ?>
        <div class="feature-chart ivory-panel" aria-label="<?= htmlspecialchars($t('carta.title'), ENT_QUOTES, 'UTF-8') ?>">
          <?= $chartSvg ?>
        </div>
      <?php elseif ($submitted && !$error): ?>
        <p class="feature-empty"><?= htmlspecialchars($t('carta.generating'), ENT_QUOTES, 'UTF-8') ?></p>
      <?php else: ?>
        <p class="feature-empty"><?= htmlspecialchars($t('carta.empty'), ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
    </div>
  </div>

  <?php
  $sectionTitle = $t('carta.interp_title');
  require dirname(__DIR__) . '/partials/planet-interpretations.php';
  ?>
</section>
