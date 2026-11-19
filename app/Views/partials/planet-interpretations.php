<?php
/** @var list<\App\Models\PlanetPosition> $primaryBodies */
/** @var list<\App\Models\PlanetPosition> $secondaryBodies */
/** @var string|null $sectionTitle */

use App\Support\PlanetMeanings;

$primaryBodies = $primaryBodies ?? [];
$secondaryBodies = $secondaryBodies ?? [];
$sectionTitle = $sectionTitle ?? 'Interpretación planetaria';

if ($primaryBodies === [] && $secondaryBodies === []) {
    return;
}

$renderCard = static function (\App\Models\PlanetPosition $body): void {
    $bid = strtolower($body->id);
    $sid = strtolower($body->signId);
    $nameEs = PlanetMeanings::BODY_ES[$bid] ?? $body->name;
    $signName = PlanetMeanings::SIGN_ES[$sid] ?? $body->sign;
    $note = PlanetMeanings::forBody($body);
    ?>
    <article class="planet-card">
      <div class="planet-card-top">
        <span class="planet-glyph"><?= PlanetMeanings::BODY_GLYPH[$bid] ?? '·' ?></span>
        <h3><?= htmlspecialchars($nameEs, ENT_QUOTES, 'UTF-8') ?></h3>
      </div>
      <p class="planet-sign">
        <span class="sign-glyph"><?= PlanetMeanings::SIGN_GLYPH[$sid] ?? '' ?></span>
        <?= htmlspecialchars($signName, ENT_QUOTES, 'UTF-8') ?>
        <?php if ($body->retrograde): ?><span class="rx">Rx</span><?php endif; ?>
      </p>
      <p class="planet-degree"><?= htmlspecialchars($body->degreeFormatted(), ENT_QUOTES, 'UTF-8') ?></p>
      <p class="planet-note"><?= htmlspecialchars($note, ENT_QUOTES, 'UTF-8') ?></p>
    </article>
    <?php
};
?>
<section class="chart-interp" aria-label="<?= htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8') ?>">
  <header class="chart-interp-head">
    <h2><?= htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8') ?></h2>
    <p>Los matices de cada planeta en su signo, en la misma clave que el Cielo del día.</p>
  </header>

  <?php if ($primaryBodies !== []): ?>
    <div class="planet-grid planet-grid-primary" aria-label="Planetas personales">
      <?php foreach ($primaryBodies as $body) { $renderCard($body); } ?>
    </div>
  <?php endif; ?>

  <?php if ($secondaryBodies !== []): ?>
    <div class="planet-grid planet-grid-outer" aria-label="Planetas exteriores">
      <?php foreach ($secondaryBodies as $body) { $renderCard($body); } ?>
    </div>
  <?php endif; ?>
</section>
