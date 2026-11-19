<?php
/** @var list<\App\Models\PlanetPosition> $primaryBodies */
/** @var list<\App\Models\PlanetPosition> $secondaryBodies */
/** @var string|null $sectionTitle */
/** @var string $lang */
/** @var callable(string, array=): string $t */

use App\Support\PlanetMeanings;

$primaryBodies = $primaryBodies ?? [];
$secondaryBodies = $secondaryBodies ?? [];
$lang = $lang ?? 'es';
$t = $t ?? static fn (string $key, array $replace = []): string => $key;
$sectionTitle = $sectionTitle ?? $t('interp.default_title');

if ($primaryBodies === [] && $secondaryBodies === []) {
    return;
}

$renderCard = static function (\App\Models\PlanetPosition $body) use ($lang): void {
    $bid = strtolower($body->id);
    $sid = strtolower($body->signId);
    $name = PlanetMeanings::bodyName($bid, $lang);
    $signName = PlanetMeanings::signName($sid, $lang);
    $note = PlanetMeanings::forBody($body, $lang);
    ?>
    <article class="planet-card">
      <div class="planet-card-top">
        <span class="planet-glyph"><?= PlanetMeanings::BODY_GLYPH[$bid] ?? '·' ?></span>
        <h3><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></h3>
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
    <p><?= htmlspecialchars($t('interp.lead'), ENT_QUOTES, 'UTF-8') ?></p>
  </header>

  <?php if ($primaryBodies !== []): ?>
    <div class="planet-grid planet-grid-primary" aria-label="<?= htmlspecialchars($t('interp.primary'), ENT_QUOTES, 'UTF-8') ?>">
      <?php foreach ($primaryBodies as $body) { $renderCard($body); } ?>
    </div>
  <?php endif; ?>

  <?php if ($secondaryBodies !== []): ?>
    <div class="planet-grid planet-grid-outer" aria-label="<?= htmlspecialchars($t('interp.secondary'), ENT_QUOTES, 'UTF-8') ?>">
      <?php foreach ($secondaryBodies as $body) { $renderCard($body); } ?>
    </div>
  <?php endif; ?>
</section>
