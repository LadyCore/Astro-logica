<?php
/** @var \App\Models\DailySky $sky */
/** @var string $momentLabel */
/** @var bool $usingLiveApi */

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

$phaseQuotes = [
    'new-moon' => 'Siembra en silencio. Lo que nace ahora pide fe.',
    'waxing-crescent' => 'Confía en tu mundo interior. Ahí reside tu brújula.',
    'first-quarter' => 'Actúa con claridad. El desafío afila tu intención.',
    'waxing-gibbous' => 'Refina lo que crece. La luz pide precisión.',
    'full-moon' => 'Ilumina y libera. La verdad brilla sin esfuerzo.',
    'waning-gibbous' => 'Comparte la cosecha. La sabiduría se multiplica al darse.',
    'last-quarter' => 'Suelta con gracia. Vaciar es también crear.',
    'waning-crescent' => 'Descansa en el misterio. El ciclo se cierra en paz.',
];
$phaseQuote = $phaseQuotes[$phase->slug] ?? 'Escucha el cielo. Tu brújula ya sabe el camino.';

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
        'aries' => 'Vitalidad directa: liderá con autenticidad y movimiento.',
        'taurus' => 'Estabilidad fértil: construí con paciencia y placer.',
        'gemini' => 'Mente ágil: comunicá, aprendé, conectá ideas.',
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
    'mercury' => [
        'default' => 'Mente en movimiento: afiná palabras y escucha.',
    ],
    'venus' => [
        'default' => 'Afecto y belleza: elegí lo que te eleva.',
    ],
    'mars' => [
        'default' => 'Deseo en acción: canalizá el fuego con propósito.',
    ],
    'jupiter' => [
        'default' => 'Expansión guiada: crecé sin perder el centro.',
    ],
    'saturn' => [
        'default' => 'Estructura sabia: el límite también protege.',
    ],
    'uranus' => [
        'default' => 'Chispa liberadora: permití lo inesperado.',
    ],
    'neptune' => [
        'default' => 'Velo poético: intuí, soñá, no te pierdas.',
    ],
    'pluto' => [
        'default' => 'Poder profundo: transformá desde la raíz.',
    ],
];

/* Six above-fold cards (2×3) fill height cleanly; Saturno leads the scroll row with outers */
$primaryIds = ['sun', 'moon', 'mercury', 'venus', 'mars', 'jupiter'];
$secondaryOrder = ['saturn', 'uranus', 'neptune', 'pluto'];
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
$secondary = [];
foreach ($secondaryOrder as $id) {
    if (isset($byId[$id])) {
        $secondary[] = $byId[$id];
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

<div class="hoy-shell">
  <aside class="hoy-art" aria-hidden="true">
    <div class="hoy-art-frame">
      <img
        src="/assets/img/mystic-woman.jpg"
        alt=""
        class="mystic-portrait"
        width="768"
        height="1408"
        decoding="async"
      >
      <div class="hoy-art-fade"></div>
      <div class="hoy-art-glow"></div>
    </div>
  </aside>

  <section class="hoy-content">
    <div class="hoy-stack">
      <header class="hoy-heading">
        <p class="hoy-date"><?= htmlspecialchars($momentLabel, ENT_QUOTES, 'UTF-8') ?></p>
        <h1 class="hoy-title">Cielo del día</h1>
        <p class="hoy-sub">La astrología de hoy, interpretada para ti.</p>
        <p class="sky-source <?= $usingLiveApi ? 'is-live' : 'is-mock' ?>">
          <?= $usingLiveApi ? 'FreeAstroAPI · en vivo' : 'Datos demo · sin API key' ?>
        </p>
      </header>

      <article class="moon-card" data-phase="<?= htmlspecialchars($phase->slug, ENT_QUOTES, 'UTF-8') ?>">
        <div class="moon-card-art" aria-hidden="true">
          <div class="moon-illustration">
            <span class="moon-disc"></span>
            <span class="moon-crater c1"></span>
            <span class="moon-crater c2"></span>
            <span class="moon-crater c3"></span>
          </div>
        </div>
        <div class="moon-card-copy">
          <p class="eyebrow">Fase lunar</p>
          <h2><?= htmlspecialchars($phaseLabel, ENT_QUOTES, 'UTF-8') ?></h2>
          <p class="moon-blurb"><?= htmlspecialchars($phaseBlurb, ENT_QUOTES, 'UTF-8') ?></p>
          <p class="moon-quote"><span class="heart" aria-hidden="true">♥</span> <?= htmlspecialchars($phaseQuote, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
      </article>

      <div class="planet-grid planet-grid-primary" aria-label="Posiciones planetarias">
        <?php foreach ($orderedPrimary as $body):
          $bid = strtolower($body->id);
          $sid = strtolower($body->signId);
          $nameEs = $bodyEs[$bid] ?? $body->name;
          $signName = $signEs[$sid] ?? $body->sign;
          ?>
          <article class="planet-card">
            <div class="planet-card-top">
              <span class="planet-glyph"><?= $bodyGlyphs[$bid] ?? '·' ?></span>
              <h3><?= htmlspecialchars($nameEs, ENT_QUOTES, 'UTF-8') ?></h3>
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
    </div>

    <?php if ($secondary !== []): ?>
      <div class="planet-grid planet-grid-outer" aria-label="Planetas exteriores">
        <?php foreach ($secondary as $body):
          $bid = strtolower($body->id);
          $sid = strtolower($body->signId);
          $nameEs = $bodyEs[$bid] ?? $body->name;
          $signName = $signEs[$sid] ?? $body->sign;
          ?>
          <article class="planet-card is-outer">
            <div class="planet-card-top">
              <span class="planet-glyph"><?= $bodyGlyphs[$bid] ?? '·' ?></span>
              <h3><?= htmlspecialchars($nameEs, ENT_QUOTES, 'UTF-8') ?></h3>
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
    <?php endif; ?>
  </section>
</div>
