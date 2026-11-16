<?php

declare(strict_types=1);

namespace App\Controllers;

abstract class Controller
{
    /** @param array<string, mixed> $config */
    public function __construct(
        protected readonly array $config,
    ) {
    }

    /** @param array<string, mixed> $data */
    protected function view(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        extract($data, EXTR_SKIP);
        $appName = $this->config['name'];
        $appUrl = $this->config['url'];
        $tagline = $this->config['tagline'] ?? 'Con los Pies en la Tierra y el Corazón en las Estrellas';
        $templateFile = dirname(__DIR__) . '/Views/' . $template . '.php';
        if (!is_readable($templateFile)) {
            http_response_code(500);
            echo 'Vista no encontrada.';
            return;
        }
        require dirname(__DIR__) . '/Views/layouts/main.php';
    }
}
