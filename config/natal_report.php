<?php

declare(strict_types=1);

/**
 * Natal PDF report layout.
 *
 * Place your Canva export here (recommended filename):
 *   storage/templates/reporte-cn.pdf
 *
 * Original Canva name can be "REPORTE CN .pdf" — save/rename it to reporte-cn.pdf
 * Coordinates are in millimeters from the top-left of each A4 page.
 * Tweak these values after dropping in the real Canva template.
 */
return [
    // Absolute or project-relative path to the Canva PDF template.
    'template' => dirname(__DIR__) . '/storage/templates/reporte-cn.pdf',

    // Page 1 — identity block (over the Canva cover).
    'cover' => [
        'name' => ['x' => 40, 'y' => 145, 'w' => 130, 'size' => 16, 'style' => 'B', 'color' => [62, 54, 44]],
        'birth_date' => ['x' => 40, 'y' => 165, 'w' => 130, 'size' => 12, 'style' => '', 'color' => [62, 54, 44]],
        'place' => ['x' => 40, 'y' => 185, 'w' => 130, 'size' => 11, 'style' => '', 'color' => [90, 78, 64]],
    ],

    // Page 2+ — planetary positions & short interpretations.
    'planets' => [
        'start_y' => 38,
        'line_height' => 21,
        'left_x' => 24,
        'width' => 162,
        'title_size' => 10,
        'body_size' => 9,
        'note_size' => 8,
        'title_color' => [62, 54, 44],
        'meta_color' => [106, 91, 72],
        'note_color' => [90, 78, 64],
        // Soft max lines before continuing on next imported/added page.
        'max_lines_per_page' => 11,
    ],
];
