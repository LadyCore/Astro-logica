<?php

declare(strict_types=1);

$env = static function (string $key, mixed $default = null): mixed {
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === '') {
        return $default;
    }
    return $value;
};

return [
    'driver' => 'mysql',
    'host' => (string) $env('DB_HOST', '127.0.0.1'),
    'port' => (int) $env('DB_PORT', 3306),
    'database' => (string) $env('DB_DATABASE', 'astro_logica'),
    'username' => (string) $env('DB_USERNAME', 'root'),
    'password' => (string) $env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
];
