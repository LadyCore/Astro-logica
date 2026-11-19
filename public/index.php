<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap/app.php';

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rtrim($uri, '/') ?: '/';
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

$routes = [
    'GET /' => [\App\Controllers\HomeController::class, 'index'],
    'GET /hoy' => [\App\Controllers\HoyController::class, 'index'],
    'GET /cielo-del-dia' => [\App\Controllers\HoyController::class, 'index'],

    'GET /carta-astral' => [\App\Controllers\NatalController::class, 'index'],
    'POST /carta-astral' => [\App\Controllers\NatalController::class, 'index'],
    'POST /carta-astral/pdf' => [\App\Controllers\NatalController::class, 'pdf'],
    'GET /carta-natal' => [\App\Controllers\NatalController::class, 'index'],
    'POST /carta-natal' => [\App\Controllers\NatalController::class, 'index'],
    'POST /carta-natal/pdf' => [\App\Controllers\NatalController::class, 'pdf'],

    'GET /revolucion-solar' => [\App\Controllers\SolarController::class, 'index'],
    'POST /revolucion-solar' => [\App\Controllers\SolarController::class, 'index'],

    'GET /transitos' => [\App\Controllers\TransitController::class, 'index'],
    'POST /transitos' => [\App\Controllers\TransitController::class, 'index'],

    'GET /tu-horoscopo' => [\App\Controllers\HoroscopeController::class, 'index'],
    'POST /tu-horoscopo' => [\App\Controllers\HoroscopeController::class, 'index'],
];

$key = $method . ' ' . $uri;

if (!isset($routes[$key])) {
    http_response_code(404);
    $title = 'No encontrado';
    $pageClass = 'page-404';
    $activeNav = '';
    $tagline = $config['tagline'] ?? 'Con los Pies en la Tierra y el Corazón en las Estrellas';
    $appName = $config['name'];
    $content = '<section class="content-panel"><h1>404</h1><p>Esta ruta aún no existe en el firmamento.</p><p><a class="text-link" href="/hoy">Volver al Cielo del día</a></p></section>';
    require dirname(__DIR__) . '/app/Views/layouts/main.php';
    exit;
}

[$class, $action] = $routes[$key];
$controller = new $class($config);
$controller->$action();
