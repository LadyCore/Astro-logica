<?php

declare(strict_types=1);

/**
 * Natal PDF report layout — Canva template "REPORTE CN .pdf".
 *
 * CRITICAL: Pages 1–2 are Canva-only (no overlays, no footer stamps).
 * Dynamic content starts at page 3.
 *
 * Generated order:
 *   1–2) Canva cover + intro (untouched)
 *   3)   Chart + consultante data (nombre, fecha, hora, lugar) + slogan footer
 *   4)   All interpretations + contact + slogan footer
 */
return [
    'template' => dirname(__DIR__) . '/storage/templates/reporte-cn.pdf',

    // Blank Canva frames for content (never used for pages 1–2 overlays).
    'content_pages' => [3, 4],

    // Shared slogan footer — ONLY stamped on content pages (3+).
    'footer' => [
        'slogan' => 'Con los Pies en la Tierra y el Corazón en las Estrellas',
        'brand' => 'Astro-lógica',
        'y' => 342,
        'size' => 8.5,
        'brand_size' => 8,
        'color' => [90, 78, 64],
        'brand_color' => [28, 24, 68],
        'left_x' => 36,
        'width' => 214,
    ],

    // Page 3 — chart (top) + identity (below).
    'chart' => [
        'heading' => [
            'es' => 'Tu Carta Natal',
            'en' => 'Your Natal Chart',
        ],
        'heading_y' => 22,
        'heading_size' => 16,
        'heading_color' => [28, 24, 68],
        'svg_x' => 48,
        'svg_y' => 38,
        'svg_w' => 190,
        'svg_h' => 190,
        'missing' => [
            'es' => 'No se pudo incluir el gráfico de la carta en este reporte.',
            'en' => 'The natal chart graphic could not be included in this report.',
        ],
    ],

    'identity' => [
        'name' => [
            'x' => 32, 'y' => 240, 'w' => 222, 'size' => 20, 'style' => 'B',
            'align' => 'C', 'color' => [28, 24, 68],
        ],
        'birth_date' => [
            'x' => 32, 'y' => 258, 'w' => 222, 'size' => 12, 'style' => '',
            'align' => 'C', 'color' => [110, 62, 48],
        ],
        'birth_time' => [
            'x' => 32, 'y' => 272, 'w' => 222, 'size' => 12, 'style' => '',
            'align' => 'C', 'color' => [110, 62, 48],
        ],
        'place' => [
            'x' => 32, 'y' => 286, 'w' => 222, 'size' => 12, 'style' => '',
            'align' => 'C', 'color' => [55, 48, 90],
        ],
    ],

    // Page 4 — all planet blocks + contact; must fit one page.
    'planets' => [
        'heading' => [
            'es' => 'Interpretación',
            'en' => 'Interpretation',
        ],
        'subheading' => [
            'es' => 'Posiciones planetarias',
            'en' => 'Planetary positions',
        ],
        'heading_y' => 20,
        'heading_size' => 15,
        'subheading_y' => 31,
        'subheading_size' => 10.5,
        'heading_color' => [28, 24, 68],
        'start_y' => 42,
        'bottom_y' => 300, // leave room for contact + slogan footer
        'left_x' => 34,
        'width' => 218,
        'title_size' => 10,
        'body_size' => 9,
        'note_size' => 8.5,
        'title_color' => [28, 24, 68],
        'meta_color' => [130, 72, 48],
        'note_color' => [48, 44, 62],
        'single_page' => true,
    ],

    // Contact block at bottom of page 4 (above slogan footer).
    'contact' => [
        'instagram' => '@astrologica5',
        'email' => 'astro.logica.uge@gmail.com',
        'y' => 308,
        'gap' => 12,
        'size' => 10.5,
        'color' => [110, 62, 48],
        'left_x' => 36,
        'width' => 214,
    ],
];
