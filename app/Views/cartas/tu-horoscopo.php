<?php
/** @var array<string, mixed> $form */
/** @var bool $submitted */
/** @var array<string, mixed>|null $horoscope */
/** @var string|null $error */
/** @var bool $apiReady */

$signs = [
    'aries' => 'Aries', 'taurus' => 'Tauro', 'gemini' => 'Géminis', 'cancer' => 'Cáncer',
    'leo' => 'Leo', 'virgo' => 'Virgo', 'libra' => 'Libra', 'scorpio' => 'Escorpio',
    'sagittarius' => 'Sagitario', 'capricorn' => 'Capricornio', 'aquarius' => 'Acuario', 'pisces' => 'Piscis',
];
$glyphs = [
    'aries' => '♈', 'taurus' => '♉', 'gemini' => '♊', 'cancer' => '♋',
    'leo' => '♌', 'virgo' => '♍', 'libra' => '♎', 'scorpio' => '♏',
    'sagittarius' => '♐', 'capricorn' => '♑', 'aquarius' => '♒', 'pisces' => '♓',
];
$selected = strtolower((string) ($form['sign'] ?? 'scorpio'));
$content = is_array($horoscope['content'] ?? null) ? $horoscope['content'] : [];
$scores = is_array($horoscope['scores'] ?? null) ? $horoscope['scores'] : [];
$lucky = is_array($horoscope['lucky'] ?? null) ? $horoscope['lucky'] : [];
$overview = (string) (
    $content['text']
    ?? $content['overview']
    ?? $content['general']
    ?? $content['summary']
    ?? ''
);
$theme = (string) ($content['theme'] ?? '');
$keywords = is_array($content['keywords'] ?? null) ? $content['keywords'] : [];
$insights = is_array($content['supporting_insights'] ?? null) ? $content['supporting_insights'] : [];
$love = (string) ($content['love'] ?? '');
$career = (string) ($content['career'] ?? '');
$money = (string) ($content['money'] ?? '');
$health = (string) ($content['health'] ?? '');
$h = static fn (mixed $v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<section class="feature-page">
  <header class="feature-hero">
    <p class="hoy-date">FREEASTROAPI · DAILY HOROSCOPE</p>
    <h1 class="hoy-title">Tu Horóscopo</h1>
    <p class="hoy-sub">El pulso diario de tu signo: amor, trabajo, dinero y salud.</p>
  </header>

  <?php if (!$apiReady): ?>
    <p class="feature-banner is-warn">Falta la API key. Agregá <code>FREE_ASTRO_API_KEY</code> en <code>.env</code>.</p>
  <?php endif; ?>
  <?php if ($error): ?>
    <p class="feature-banner is-error"><?= $h($error) ?></p>
  <?php endif; ?>

  <form class="astro-form horoscope-form" method="post" action="/tu-horoscopo">
    <h2>Elegí tu signo solar</h2>
    <div class="sign-picker" role="listbox" aria-label="Signos del zodiaco">
      <?php foreach ($signs as $id => $label): ?>
        <label class="sign-chip<?= $selected === $id ? ' is-selected' : '' ?>">
          <input type="radio" name="sign" value="<?= $h($id) ?>" <?= $selected === $id ? 'checked' : '' ?>>
          <span class="sign-chip-glyph" aria-hidden="true"><?= $glyphs[$id] ?? '·' ?></span>
          <span class="sign-chip-label"><?= $h($label) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="astro-submit">Ver horóscopo de hoy</button>
  </form>

  <?php if (is_array($horoscope)): ?>
    <div class="horoscope-result">
      <div class="horoscope-head">
        <p class="hoy-date"><?= $h(strtoupper((string) ($horoscope['date'] ?? 'HOY'))) ?></p>
        <h2><?= $h($signs[$selected] ?? $selected) ?> <?= $glyphs[$selected] ?? '' ?></h2>
        <?php if ($theme !== ''): ?>
          <p class="hoy-sub">Tema del día: <?= $h($theme) ?></p>
        <?php endif; ?>
      </div>

      <?php if ($scores !== []): ?>
        <div class="score-grid" aria-label="Puntajes del día">
          <?php foreach (['overall' => 'General', 'love' => 'Amor', 'career' => 'Carrera', 'money' => 'Dinero', 'health' => 'Salud'] as $key => $label):
            if (!isset($scores[$key])) continue;
            $val = (int) $scores[$key];
            ?>
            <article class="score-card">
              <p class="score-label"><?= $h($label) ?></p>
              <p class="score-value"><?= $val ?></p>
              <div class="score-bar" aria-hidden="true"><span style="width: <?= max(0, min(100, $val)) ?>%"></span></div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($overview !== ''): ?>
        <article class="horoscope-block">
          <h3>Panorama</h3>
          <p><?= nl2br($h($overview)) ?></p>
        </article>
      <?php endif; ?>

      <?php if ($keywords !== []): ?>
        <p class="lucky-line">
          <?= $h(implode(' · ', array_map('strval', $keywords))) ?>
        </p>
      <?php endif; ?>

      <?php if ($insights !== []): ?>
        <div class="horoscope-columns">
          <?php foreach ($insights as $insight):
            if (!is_string($insight) || $insight === '') continue;
            ?>
            <article class="horoscope-block">
              <h3>Insight</h3>
              <p><?= nl2br($h($insight)) ?></p>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="horoscope-columns">
        <?php foreach ([
            'Amor' => $love,
            'Carrera' => $career,
            'Dinero' => $money,
            'Salud' => $health,
        ] as $title => $text):
          if ($text === '') continue;
          ?>
          <article class="horoscope-block">
            <h3><?= $h($title) ?></h3>
            <p><?= nl2br($h($text)) ?></p>
          </article>
        <?php endforeach; ?>
      </div>

      <?php if ($lucky !== []): ?>
        <p class="lucky-line">
          <?php
          $bits = [];
          foreach (['number' => 'Número', 'color' => 'Color', 'time' => 'Hora'] as $k => $lab) {
              if (!empty($lucky[$k])) {
                  $bits[] = $lab . ': ' . (is_array($lucky[$k]) ? implode(', ', $lucky[$k]) : (string) $lucky[$k]);
              }
          }
          echo $h(implode(' · ', $bits));
          ?>
        </p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>
