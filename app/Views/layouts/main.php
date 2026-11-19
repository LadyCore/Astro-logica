<?php
/** @var string $title */
/** @var string $pageClass */
/** @var string $appName */
/** @var string $tagline */
/** @var string $activeNav */
$appName = $appName ?? 'Astro-lógica';
$tagline = $tagline ?? 'Con los Pies en la Tierra y el Corazón en las Estrellas';
$pageClass = $pageClass ?? '';
$activeNav = $activeNav ?? '';
$activeCard = $activeCard ?? '';
$title = $title ?? $appName;
$contentFile = $templateFile ?? null;
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
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

    <nav class="site-nav" aria-label="Principal">
      <a href="/" class="nav-link<?= $activeNav === 'inicio' ? ' is-active' : '' ?>">Inicio</a>
      <a href="/hoy" class="nav-link<?= $activeNav === 'hoy' ? ' is-active' : '' ?>">Cielo del día</a>

      <div class="nav-dropdown<?= $activeNav === 'cartas' ? ' is-active' : '' ?>">
        <button
          type="button"
          class="nav-link nav-dropdown-toggle<?= $activeNav === 'cartas' ? ' is-active' : '' ?>"
          aria-expanded="false"
          aria-haspopup="true"
          aria-controls="cartas-menu"
          id="cartas-toggle"
        >
          Cartas
          <span class="nav-caret" aria-hidden="true"></span>
        </button>
        <div class="nav-dropdown-menu" id="cartas-menu" role="menu" hidden>
          <a href="/carta-astral" class="nav-dropdown-item<?= ($activeCard ?? '') === 'carta-astral' ? ' is-active' : '' ?>" role="menuitem">Carta Astral</a>
          <a href="/revolucion-solar" class="nav-dropdown-item<?= ($activeCard ?? '') === 'revolucion-solar' ? ' is-active' : '' ?>" role="menuitem">Revolución Solar</a>
          <a href="/transitos" class="nav-dropdown-item<?= ($activeCard ?? '') === 'transitos' ? ' is-active' : '' ?>" role="menuitem">Tránsitos</a>
        </div>
      </div>

      <a href="/tu-horoscopo" class="nav-link<?= $activeNav === 'horoscopo' ? ' is-active' : '' ?>">Tu Horóscopo</a>
    </nav>

    <div class="header-actions">
      <button type="button" class="icon-btn theme-toggle" id="theme-toggle" aria-label="Cambiar tema">
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
