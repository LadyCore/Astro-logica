<?php
/** @var string $title */
/** @var string $pageClass */
/** @var string $appName */
/** @var string $tagline */
/** @var string $activeNav */
/** @var string $activeCard */
/** @var \App\Support\I18n $i18n */
/** @var callable(string, array=): string $t */
/** @var string $lang */

$appName = $appName ?? 'Astro-lógica';
$i18n = $i18n ?? new \App\Support\I18n();
$lang = $lang ?? $i18n->locale();
$t = $t ?? static fn (string $key, array $replace = []): string => $i18n->t($key, $replace);
$tagline = $tagline ?? $t('tagline');
$pageClass = $pageClass ?? '';
$activeNav = $activeNav ?? '';
$activeCard = $activeCard ?? '';
$title = $title ?? $appName;
$contentFile = $templateFile ?? null;

$langSwitchUrl = static function (string $code) use ($lang): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $parts = parse_url($uri);
    $path = $parts['path'] ?? '/';
    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    $query['lang'] = $code;
    return $path . '?' . http_build_query($query);
};
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>" data-theme="dark" data-lang="<?= htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="dark light">
  <title><?= htmlspecialchars($title . ' · ' . $appName, ENT_QUOTES, 'UTF-8') ?></title>
  <meta name="description" content="<?= htmlspecialchars($appName . ' — ' . $tagline, ENT_QUOTES, 'UTF-8') ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Manrope:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/app.css">
  <script>
    (function () {
      try {
        var stored = localStorage.getItem('astro-theme');
        var theme = stored || 'dark';
        document.documentElement.setAttribute('data-theme', theme);
        var lang = localStorage.getItem('astro-lang');
        if (lang === 'es' || lang === 'en') {
          document.documentElement.setAttribute('data-lang', lang);
        }
      } catch (e) {}
    })();
  </script>
</head>
<body class="<?= htmlspecialchars($pageClass, ENT_QUOTES, 'UTF-8') ?>">
  <div class="cosmic-bg" aria-hidden="true">
    <canvas id="starfield" class="starfield"></canvas>
    <div class="constellation-lines"></div>
  </div>

  <header class="site-header">
    <a class="brand" href="/">
      <span class="brand-star" aria-hidden="true">
        <svg viewBox="0 0 32 32" width="28" height="28" fill="none">
          <circle cx="16" cy="16" r="3" fill="currentColor"/>
          <path d="M16 2v6M16 24v6M2 16h6M24 16h6M6.5 6.5l4.2 4.2M21.3 21.3l4.2 4.2M25.5 6.5l-4.2 4.2M10.7 21.3l-4.2 4.2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
          <circle cx="16" cy="16" r="9" stroke="currentColor" stroke-width="1" opacity=".55"/>
        </svg>
      </span>
      <span class="brand-text">
        <span class="brand-name">Astro-lógica</span>
        <span class="brand-tagline"><?= htmlspecialchars($tagline, ENT_QUOTES, 'UTF-8') ?></span>
      </span>
    </a>

    <nav class="site-nav" aria-label="<?= htmlspecialchars($t('nav.aria'), ENT_QUOTES, 'UTF-8') ?>">
      <a href="/" class="nav-link<?= $activeNav === 'inicio' ? ' is-active' : '' ?>"><?= htmlspecialchars($t('nav.home'), ENT_QUOTES, 'UTF-8') ?></a>
      <a href="/hoy" class="nav-link<?= $activeNav === 'hoy' ? ' is-active' : '' ?>"><?= htmlspecialchars($t('nav.hoy'), ENT_QUOTES, 'UTF-8') ?></a>

      <div class="nav-dropdown<?= $activeNav === 'cartas' ? ' is-active' : '' ?>">
        <button
          type="button"
          class="nav-link nav-dropdown-toggle<?= $activeNav === 'cartas' ? ' is-active' : '' ?>"
          aria-expanded="false"
          aria-haspopup="true"
          aria-controls="cartas-menu"
          id="cartas-toggle"
        >
          <?= htmlspecialchars($t('nav.cartas'), ENT_QUOTES, 'UTF-8') ?>
          <span class="nav-caret" aria-hidden="true"></span>
        </button>
        <div class="nav-dropdown-menu" id="cartas-menu" role="menu" hidden>
          <a href="/carta-astral" class="nav-dropdown-item<?= ($activeCard ?? '') === 'carta-astral' ? ' is-active' : '' ?>" role="menuitem"><?= htmlspecialchars($t('nav.carta_astral'), ENT_QUOTES, 'UTF-8') ?></a>
          <a href="/revolucion-solar" class="nav-dropdown-item<?= ($activeCard ?? '') === 'revolucion-solar' ? ' is-active' : '' ?>" role="menuitem"><?= htmlspecialchars($t('nav.revolucion_solar'), ENT_QUOTES, 'UTF-8') ?></a>
          <a href="/transitos" class="nav-dropdown-item<?= ($activeCard ?? '') === 'transitos' ? ' is-active' : '' ?>" role="menuitem"><?= htmlspecialchars($t('nav.transitos'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
      </div>

      <a href="/tu-horoscopo" class="nav-link<?= $activeNav === 'horoscopo' ? ' is-active' : '' ?>"><?= htmlspecialchars($t('nav.horoscopo'), ENT_QUOTES, 'UTF-8') ?></a>
    </nav>

    <div class="header-actions">
      <div class="lang-switch" role="group" aria-label="<?= htmlspecialchars($t('lang.label'), ENT_QUOTES, 'UTF-8') ?>">
        <a
          href="<?= htmlspecialchars($langSwitchUrl('es'), ENT_QUOTES, 'UTF-8') ?>"
          class="lang-btn<?= $lang === 'es' ? ' is-active' : '' ?>"
          data-lang="es"
          hreflang="es"
          lang="es"
        ><?= htmlspecialchars($t('lang.es'), ENT_QUOTES, 'UTF-8') ?></a>
        <a
          href="<?= htmlspecialchars($langSwitchUrl('en'), ENT_QUOTES, 'UTF-8') ?>"
          class="lang-btn<?= $lang === 'en' ? ' is-active' : '' ?>"
          data-lang="en"
          hreflang="en"
          lang="en"
        ><?= htmlspecialchars($t('lang.en'), ENT_QUOTES, 'UTF-8') ?></a>
      </div>
      <button type="button" class="icon-btn" id="site-search-btn" aria-label="<?= htmlspecialchars($t('nav.search'), ENT_QUOTES, 'UTF-8') ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
          <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.5"/>
          <path d="M16.2 16.2 20 20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
        </svg>
      </button>
      <a href="/carta-astral" class="icon-btn" aria-label="<?= htmlspecialchars($t('nav.profile'), ENT_QUOTES, 'UTF-8') ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.4"/>
          <circle cx="12" cy="10" r="3.2" stroke="currentColor" stroke-width="1.4"/>
          <path d="M6.8 18.2c1.4-2.2 3.2-3.2 5.2-3.2s3.8 1 5.2 3.2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
        </svg>
      </a>
      <button type="button" class="icon-btn theme-toggle" id="theme-toggle" aria-label="<?= htmlspecialchars($t('theme.toggle'), ENT_QUOTES, 'UTF-8') ?>">
        <span class="theme-icon" aria-hidden="true"></span>
      </button>
    </div>
  </header>

  <main id="main">
    <?php
    if (isset($content) && is_string($content)) {
        echo $content;
    } elseif ($contentFile) {
        require $contentFile;
    }
    ?>
  </main>

  <footer class="site-footer">
    <p><strong>Astro-lógica</strong> · <?= htmlspecialchars($tagline, ENT_QUOTES, 'UTF-8') ?></p>
  </footer>

  <script src="/assets/js/app.js" defer></script>
</body>
</html>
