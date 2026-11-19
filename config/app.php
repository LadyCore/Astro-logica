<?php

declare(strict_types=1);

$env = static function (string $key, mixed $default = null): mixed {
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return $value;
};

// Prefer FREE_ASTRO_API_KEY (FreeAstroAPI /llms.txt integration name).
$apiKey = $env('FREE_ASTRO_API_KEY')
    ?? $env('FREEASTROAPI_KEY')
    ?? $env('FREEASTRO_API_KEY');

return [
    'name' => (string) $env('APP_NAME', 'Astro-lógica'),
    'env' => (string) $env('APP_ENV', 'local'),
    'debug' => filter_var($env('APP_DEBUG', true), FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim((string) $env('APP_URL', 'http://127.0.0.1:8080'), '/'),
    'timezone' => (string) $env('APP_TIMEZONE', 'America/Argentina/Buenos_Aires'),
    'tagline' => 'Con los Pies en la Tierra y el Corazón en las Estrellas',

    'astro_api' => [
        'base_url' => rtrim((string) $env('FREEASTROAPI_BASE_URL', 'https://api.freeastroapi.com'), '/'),
        'api_key' => $apiKey ? (string) $apiKey : null,
        'timeout' => 20,
        'ephemeris_path' => '/api/v1/ephemeris/calculate',
        'natal_path' => '/api/v1/natal/calculate',
        'chart_svg_path' => '/api/v1/natal/chart/',
        'bodies' => [
            'Sun', 'Moon', 'Mercury', 'Venus', 'Mars',
            'Jupiter', 'Saturn', 'Uranus', 'Neptune', 'Pluto',
        ],
        // Default location for "Cielo del día" wheel (Buenos Aires)
        'sky_city' => (string) $env('ASTRO_SKY_CITY', 'Buenos Aires'),
        'sky_lat' => (float) $env('ASTRO_SKY_LAT', -34.6037),
        'sky_lng' => (float) $env('ASTRO_SKY_LNG', -58.3816),
        'chart_cache_seconds' => (int) $env('ASTRO_CHART_CACHE_SECONDS', 900),
    ],

    'database' => require __DIR__ . '/database.php',
];
