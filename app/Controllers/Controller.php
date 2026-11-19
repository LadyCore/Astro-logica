<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Support\I18n;

abstract class Controller
{
    protected I18n $i18n;

    /** @param array<string, mixed> $config */
    public function __construct(
        protected readonly array $config,
    ) {
        $this->i18n = new I18n();
    }

    /** @param array<string, mixed> $data */
    protected function view(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        $i18n = $this->i18n;
        $lang = $i18n->locale();
        $t = static fn (string $key, array $replace = []): string => $i18n->t($key, $replace);

        extract($data, EXTR_SKIP);
        $appName = $this->config['name'];
        $appUrl = $this->config['url'];
        $tagline = $t('tagline');
        $templateFile = dirname(__DIR__) . '/Views/' . $template . '.php';
        if (!is_readable($templateFile)) {
            http_response_code(500);
            echo $lang === 'en' ? 'View not found.' : 'Vista no encontrada.';
            return;
        }
        require dirname(__DIR__) . '/Views/layouts/main.php';
    }

    protected function lang(): string
    {
        return $this->i18n->locale();
    }

    protected function t(string $key, array $replace = []): string
    {
        return $this->i18n->t($key, $replace);
    }
}
