<?php
/** @var \App\Models\DailySky $sky */
/** @var string $momentLabel */
/** @var bool $usingLiveApi */
/** @var string $skySourceLabel */
/** @var array{slug:string,name:string,range:string,blurb:string,glyph:string} $season */

$phase = $sky->lunarPhase;

$signGlyphs = [
    'aries' => '♈', 'taurus' => '♉', 'gemini' => '♊', 'cancer' => '♋',
    'leo' => '♌', 'virgo' => '♍', 'libra' => '♎', 'scorpio' => '♏',
    'sagittarius' => '♐', 'capricorn' => '♑', 'aquarius' => '♒', 'pisces' => '♓',
];
$bodyGlyphs = [
    'sun' => '☉', 'moon' => '☽', 'mercury' => '☿', 'venus' => '♀', 'mars' => '♂',
    'jupiter' => '♃', 'saturn' => '♄', 'uranus' => '♅', 'neptune' => '♆', 'pluto' => '♇',
];
$signEs = [
    'aries' => 'Aries', 'taurus' => 'Tauro', 'gemini' => 'Géminis', 'cancer' => 'Cáncer',
    'leo' => 'Leo', 'virgo' => 'Virgo', 'libra' => 'Libra', 'scorpio' => 'Escorpio',
    'sagittarius' => 'Sagitario', 'capricorn' => 'Capricornio', 'aquarius' => 'Acuario', 'pisces' => 'Piscis',
];
$bodyEs = [
    'sun' => 'Sol', 'moon' => 'Luna', 'mercury' => 'Mercurio', 'venus' => 'Venus', 'mars' => 'Marte',
    'jupiter' => 'Júpiter', 'saturn' => 'Saturno', 'uranus' => 'Urano', 'neptune' => 'Neptuno', 'pluto' => 'Plutón',
];
$phaseEs = [
    'new-moon' => 'Luna Nueva',
    'waxing-crescent' => 'Luna Creciente',
    'first-quarter' => 'Cuarto Creciente',
    'waxing-gibbous' => 'Gibosa Creciente',
    'full-moon' => 'Luna Llena',
    'waning-gibbous' => 'Gibosa Menguante',
    'last-quarter' => 'Cuarto Menguante',
    'waning-crescent' => 'Luna Menguante',
];
$phaseLabel = $phaseEs[$phase->slug] ?? $phase->name;

$phaseBlurbs = [
    'new-moon' => 'Un cielo de comienzos. La Luna invita a plantar intenciones con calma y honestidad.',
    'waxing-crescent' => 'La Luna crece y invita a nutrir lo sensible: emociones, hogar y vínculos que sostienen.',
    'first-quarter' => 'Tensión creativa: decide, ajusta el rumbo y da el siguiente paso con coraje sereno.',
    'waxing-gibbous' => 'Afiná detalles. El cielo favorece la paciencia y el pulido de lo que ya está en marcha.',
    'full-moon' => 'Culminación y revelación. Lo oculto se hace visible; celebra y suelta con lucidez.',
    'waning-gibbous' => 'Tiempo de integrar aprendizajes y ofrecer lo aprendido con generosidad.',
    'last-quarter' => 'Revisión y cierre. Distingue qué conservar y qué dejar atrás.',
    'waning-crescent' => 'Recogimiento sagrado. Descansa, sueña y prepara el próximo ciclo.',
];

$moonBody = $sky->body('Moon');
$moonSignId = $moonBody ? strtolower($moonBody->signId) : 'cancer';
$moonSignName = $signEs[$moonSignId] ?? ($moonBody->sign ?? 'Cáncer');
$phaseBlurb = ($phaseBlurbs[$phase->slug] ?? 'La Luna marca el pulso emocional del día.')
    . ' Luna en ' . $moonSignName . ': ' .
    match ($moonSignId) {
        'aries' => 'impulso vital y coraje limpio.',
        'taurus' => 'placeres simples y constancia sensual.',
        'gemini' => 'curiosidad, palabras y conexiones ágiles.',
        'cancer' => 'cuidado, memoria afectiva y nido interior.',
        'leo' => 'brillo creativo y generosidad del corazón.',
        'virgo' => 'orden amoroso y atención a lo pequeño.',
        'libra' => 'belleza, diálogo y equilibrios delicados.',
        'scorpio' => 'intensidad, verdad profunda y transformación.',
        'sagittarius' => 'fe expansiva y sed de horizonte.',
        'capricorn' => 'estructura, ambición serena y madurez.',
        'aquarius' => 'visión colectiva e independencia lúcida.',
        'pisces' => 'sueño, empatía y disolución creativa.',
        default => 'escuchá el matiz del signo.',
    };

$interpretations = [
    'sun' => [
        'aries' => 'Vitalidad directa: liderá con autenticidad.',
        'taurus' => 'Estabilidad fértil: construí con paciencia.',
        'gemini' => 'Mente ágil: comunicá y conectá ideas.',
        'cancer' => 'Brillo protector: el hogar emocional es tu sol.',
        'leo' => 'Presencia magnética: creá y dejá que te vean.',
        'virgo' => 'Luz precisa: el servicio consciente también ilumina.',
        'libra' => 'Elegancia relacional: armonizá sin perderte.',
        'scorpio' => 'Fuerza íntima: transforma lo que ya no late.',
        'sagittarius' => 'Fuego filosófico: expandí sentido y horizonte.',
        'capricorn' => 'Autoridad serena: el tiempo premia tu constancia.',
        'aquarius' => 'Visión futura: innová con conciencia colectiva.',
        'pisces' => 'Inspiración fluida: soñá y canalizá lo sutil.',
    ],
    'moon' => [
        'aries' => 'Emoción inmediata: sentí y actuá sin dramatizar.',
        'taurus' => 'Calma sensorial: nutrite con lo tangible.',
        'gemini' => 'Ánimo conversador: nombrá lo que sentís.',
        'cancer' => 'Marea afectiva: cuidá tu nido interior.',
        'leo' => 'Corazón visible: necesitás reconocimiento cálido.',
        'virgo' => 'Cuidado práctico: ordená para sentirte a salvo.',
        'libra' => 'Sensibilidad estética: buscá belleza en el vínculo.',
        'scorpio' => 'Profundidad emocional: no temas la intensidad.',
        'sagittarius' => 'Ánimo viajero: la fe alivia el pecho.',
        'capricorn' => 'Contención madura: límites que abrazan.',
        'aquarius' => 'Distancia lúcida: sentí sin ahogarte.',
        'pisces' => 'Empatía oceánica: filtrá para no diluirte.',
    ],
    'mercury' => ['default' => 'Mente en movimiento: afiná palabras y escuchá.'],
    'venus' => ['default' => 'Afecto y belleza: elegí lo que te eleva.'],
    'mars' => ['default' => 'Deseo en acción: canalizá el fuego con propósito.'],
    'jupiter' => ['default' => 'Expansión guiada: crecé sin perder el centro.'],
];

$primaryIds = ['sun', 'moon', 'mercury', 'venus', 'mars', 'jupiter'];
$byId = [];
foreach ($sky->bodies as $body) {
    $byId[strtolower($body->id)] = $body;
}
$orderedPrimary = [];
foreach ($primaryIds as $id) {
    if (isset($byId[$id])) {
        $orderedPrimary[] = $byId[$id];
    }
}

$interpFor = static function ($body) use ($interpretations): string {
    $id = strtolower($body->id);
    $sid = strtolower($body->signId);
    if (isset($interpretations[$id][$sid])) {
        return $interpretations[$id][$sid];
    }
    $base = $interpretations[$id]['default'] ?? 'El cielo habla: escuchá su matiz.';
    if ($body->retrograde) {
        return $base . ' (Rx: revisá hacia adentro.)';
    }
    return $base;
};
?>

<div class="home-landing">
  <section class="home-hero">
    <div class="home-hero-copy">
      <p class="home-eyebrow">Astrología contemporánea</p>
      <h1 class="home-hero-title">Astrología para una vida más consciente</h1>
      <p class="home-hero-lead">
        Ciclos, símbolos y autocuidado para leer el cielo con los pies en la tierra
        y el corazón abierto a lo que se revela.
      </p>
      <div class="home-hero-ctas">
        <a class="btn btn-gold" href="/hoy">Explorar el cielo de hoy <span aria-hidden="true">→</span></a>
        <a class="btn btn-outline" href="/carta-natal">Conocer cartas <span aria-hidden="true">→</span></a>
      </div>
    </div>
    <div class="home-hero-art" aria-hidden="true">
      <div class="home-hero-frame">
        <img
          src="/assets/img/mystic-woman-frontal.jpg"
          alt=""
          width="900"
          height="1100"
          decoding="async"
        >
        <div class="home-hero-glow"></div>
      </div>
    </div>
  </section>

  <section class="home-teaser" aria-labelledby="home-teaser-title">
    <div class="home-teaser-date">
      <p class="home-teaser-when"><?= htmlspecialchars($momentLabel, ENT_QUOTES, 'UTF-8') ?></p>
      <h2 id="home-teaser-title">Cielo del día</h2>
      <p class="home-teaser-source <?= $usingLiveApi ? 'is-live' : 'is-mock' ?>">
        <?= htmlspecialchars($skySourceLabel ?? ($usingLiveApi ? 'FreeAstroAPI · en vivo' : 'Datos demo · sin API key'), ENT_QUOTES, 'UTF-8') ?>
      </p>
    </div>
    <div class="home-teaser-phase" data-phase="<?= htmlspecialchars($phase->slug, ENT_QUOTES, 'UTF-8') ?>">
      <p class="eyebrow">Fase lunar</p>
      <p class="home-teaser-phase-name"><?= htmlspecialchars($phaseLabel, ENT_QUOTES, 'UTF-8') ?></p>
      <div class="moon-illustration home-teaser-moon" aria-hidden="true">
        <span class="moon-disc"></span>
        <span class="moon-crater c1"></span>
        <span class="moon-crater c2"></span>
        <span class="moon-crater c3"></span>
      </div>
    </div>
    <div class="home-teaser-copy">
      <p><?= htmlspecialchars($phaseBlurb, ENT_QUOTES, 'UTF-8') ?></p>
      <a class="home-teaser-link" href="/hoy" aria-label="Ver el cielo de hoy">
        <span aria-hidden="true">→</span>
      </a>
    </div>
  </section>

  <section class="home-planets" aria-labelledby="home-planets-title">
    <div class="home-section-head">
      <h2 id="home-planets-title" class="home-section-title">Posiciones planetarias</h2>
      <a class="home-section-link" href="/hoy">Ver el mapa completo <span aria-hidden="true">→</span></a>
    </div>
    <div class="home-planet-grid">
      <?php foreach ($orderedPrimary as $body):
        $bid = strtolower($body->id);
        $sid = strtolower($body->signId);
        $nameEs = $bodyEs[$bid] ?? $body->name;
        $signName = $signEs[$sid] ?? $body->sign;
        ?>
        <article class="home-planet-card">
          <div class="home-planet-top">
            <h3><?= htmlspecialchars($nameEs, ENT_QUOTES, 'UTF-8') ?></h3>
            <span class="planet-glyph"><?= $bodyGlyphs[$bid] ?? '·' ?></span>
          </div>
          <p class="planet-sign">
            <span class="sign-glyph"><?= $signGlyphs[$sid] ?? '' ?></span>
            <?= htmlspecialchars($signName, ENT_QUOTES, 'UTF-8') ?>
            <?php if ($body->retrograde): ?><span class="rx">Rx</span><?php endif; ?>
          </p>
          <p class="planet-degree"><?= htmlspecialchars($body->degreeFormatted(), ENT_QUOTES, 'UTF-8') ?></p>
          <p class="planet-note"><?= htmlspecialchars($interpFor($body), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="home-features" aria-label="Cartas y ciclos">
    <article class="home-feature-card">
      <div class="home-feature-art natal-wheel" aria-hidden="true">
        <svg viewBox="0 0 200 200" width="112" height="112" fill="none" aria-hidden="true">
          <circle cx="100" cy="100" r="88" stroke="currentColor" stroke-width="1.2" opacity=".55"/>
          <circle cx="100" cy="100" r="62" stroke="currentColor" stroke-width="1" opacity=".4"/>
          <circle cx="100" cy="100" r="28" stroke="currentColor" stroke-width="1" opacity=".7"/>
          <circle cx="100" cy="100" r="4" fill="currentColor"/>
          <?php for ($i = 0; $i < 12; $i++):
            $a = deg2rad($i * 30 - 90);
            $x1 = 100 + cos($a) * 62;
            $y1 = 100 + sin($a) * 62;
            $x2 = 100 + cos($a) * 88;
            $y2 = 100 + sin($a) * 88;
            ?>
            <line x1="<?= $x1 ?>" y1="<?= $y1 ?>" x2="<?= $x2 ?>" y2="<?= $y2 ?>" stroke="currentColor" stroke-width="1" opacity=".45"/>
          <?php endfor; ?>
          <path d="M100 12 L104 28 L100 24 L96 28 Z" fill="currentColor" opacity=".85"/>
        </svg>
      </div>
      <div class="home-feature-copy">
        <h2>Carta natal</h2>
        <p>Tu mapa de nacimiento: signos, casas y aspectos para comprender tu arquitectura interior.</p>
        <a class="btn btn-gold" href="/carta-natal">Calcular mi carta <span aria-hidden="true">→</span></a>
      </div>
    </article>

    <article class="home-feature-card">
      <div class="home-feature-art solar-orb" aria-hidden="true">
        <span class="solar-planet"></span>
        <span class="solar-ring r1"></span>
        <span class="solar-ring r2"></span>
        <span class="solar-ring r3"></span>
      </div>
      <div class="home-feature-copy">
        <h2>Revolución solar</h2>
        <p>El cielo de tu cumpleaños cada año: un ciclo personal para orientar decisiones y deseos.</p>
        <a class="btn btn-outline" href="/carta-natal">Explorar mi revolución <span aria-hidden="true">→</span></a>
      </div>
    </article>
  </section>

  <section class="home-season" aria-labelledby="home-season-title">
    <div class="home-season-glyph" aria-hidden="true">
      <span class="season-sign"><?= htmlspecialchars($season['glyph'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="home-season-copy">
      <p class="eyebrow">Temporada zodiacal</p>
      <h2 id="home-season-title">Temporada de <?= htmlspecialchars($season['name'], ENT_QUOTES, 'UTF-8') ?></h2>
      <p class="home-season-range"><?= htmlspecialchars($season['range'], ENT_QUOTES, 'UTF-8') ?></p>
      <p class="home-season-blurb"><?= htmlspecialchars($season['blurb'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="home-season-cta">
      <a class="btn btn-outline" href="/hoy">Leer la guía del ciclo <span aria-hidden="true">→</span></a>
    </div>
  </section>
</div>
