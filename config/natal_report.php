<?php

declare(strict_types=1);

/**
 * Natal PDF report layout — tuned for the Canva template "REPORTE CN .pdf".
 *
 * Place / replace the file at:
 *   storage/templates/reporte-cn.pdf
 *
 * Canva page size ≈ 285.75 × 357.19 mm (not A4). Coordinates are mm from top-left.
 *
 * Generated page order:
 *   1) Cover — datos del consultante (sobre el diseño, zona libre)
 *   2) Carta natal — gráfico SVG
 *   3+) Interpretación — posiciones planetarias
 *
 * Template pages: 1 cover · 2 intro (unused in export order) · 3–4 blank frames
 */
return [
    'template' => dirname(__DIR__) . '/storage/templates/reporte-cn.pdf',

    // Blank frames for chart + planet rows (cycled when needed).
    'content_pages' => [3, 4],

    // Page 1 — identity in the empty band under the author block (drawn last = on top).
    'cover' => [
        'name' => [
            'x' => 32, 'y' => 268, 'w' => 222, 'size' => 26, 'style' => 'B',
            'align' => 'C', 'color' => [28, 24, 68],
        ],
        'birth_date' => [
            'x' => 32, 'y' => 288, 'w' => 222, 'size' => 15, 'style' => '',
            'align' => 'C', 'color' => [110, 62, 48],
        ],
        'place' => [
            'x' => 32, 'y' => 302, 'w' => 222, 'size' => 14, 'style' => '',
            'align' => 'C', 'color' => [55, 48, 90],
        ],
    ],

    // Chart graphic page (blank Canva frame + SVG wheel).
    'chart' => [
        'heading' => [
            'es' => 'Tu Carta Natal',
            'en' => 'Your Natal Chart',
        ],
        'heading_y' => 28,
        'heading_size' => 20,
        'heading_color' => [28, 24, 68],
        'svg_x' => 28,
        'svg_y' => 52,
        'svg_w' => 230,
        'svg_h' => 230,
        'missing' => [
            'es' => 'No se pudo incluir el gráfico de la carta en este reporte.',
            'en' => 'The natal chart graphic could not be included in this report.',
        ],
    ],

    // Planetary positions & short interpretations (larger type for readability).
    'planets' => [
        'heading' => [
            'es' => 'Interpretación',
            'en' => 'Interpretation',
        ],
        'subheading' => [
            'es' => 'Posiciones planetarias',
            'en' => 'Planetary positions',
        ],
        'heading_y' => 26,
        'heading_size' => 20,
        'subheading_y' => 40,
        'subheading_size' => 13,
        'heading_color' => [28, 24, 68],
        'start_y' => 54,
        'line_height' => 28,
        'left_x' => 36,
        'width' => 214,
        'title_size' => 13,
        'body_size' => 12,
        'note_size' => 11,
        'title_color' => [28, 24, 68],
        'meta_color' => [130, 72, 48],
        'note_color' => [48, 44, 62],
        'max_lines_per_page' => 9,
    ],
];
