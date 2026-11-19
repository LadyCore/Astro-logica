<?php

declare(strict_types=1);

/**
 * Natal PDF report layout — tuned for the Canva template "REPORTE CN .pdf".
 *
 * Place / replace the file at:
 *   storage/templates/reporte-cn.pdf
 *
 * Canva page size ≈ 285.75 × 357.19 mm (not A4). Coordinates are mm from top-left.
 * Pages: 1 cover · 2 intro (kept as-is) · 3–4 blank content frames for planets.
 */
return [
    'template' => dirname(__DIR__) . '/storage/templates/reporte-cn.pdf',

    // After the cover, keep these template pages untouched (intro copy).
    'passthrough_pages' => [2],

    // Blank frames used to stamp planet rows (cycled if more bodies than fit).
    'content_pages' => [3, 4],

    // Page 1 — identity under "Astróloga y Tarotista", above the sunburst.
    'cover' => [
        'name' => [
            'x' => 42.9, 'y' => 276, 'w' => 200, 'size' => 18, 'style' => 'B',
            'align' => 'C', 'color' => [35, 32, 72],
        ],
        'birth_date' => [
            'x' => 42.9, 'y' => 290, 'w' => 200, 'size' => 12, 'style' => '',
            'align' => 'C', 'color' => [90, 58, 48],
        ],
        'place' => [
            'x' => 42.9, 'y' => 302, 'w' => 200, 'size' => 11, 'style' => '',
            'align' => 'C', 'color' => [70, 62, 88],
        ],
    ],

    // Pages 3+ — planetary positions & short interpretations.
    'planets' => [
        'heading' => [
            'es' => 'Posiciones planetarias',
            'en' => 'Planetary positions',
        ],
        'heading_y' => 28,
        'heading_size' => 16,
        'heading_color' => [35, 32, 72],
        'start_y' => 48,
        'line_height' => 26,
        'left_x' => 36,
        'width' => 214,
        'title_size' => 11,
        'body_size' => 10,
        'note_size' => 9,
        'title_color' => [35, 32, 72],
        'meta_color' => [120, 72, 55],
        'note_color' => [55, 50, 70],
        'max_lines_per_page' => 10,
    ],
];
