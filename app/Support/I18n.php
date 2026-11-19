<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Lightweight ES/EN translator. Default locale: Spanish.
 */
final class I18n
{
    private string $locale;

    /** @var array<string, string> */
    private array $lines;

    public function __construct(?string $locale = null)
    {
        $this->locale = self::normalize($locale ?? self::detect());
        $this->lines = self::load($this->locale);
    }

    public static function detect(): string
    {
        $fromQuery = isset($_GET['lang']) ? strtolower((string) $_GET['lang']) : '';
        if (in_array($fromQuery, ['es', 'en'], true)) {
            self::persist($fromQuery);
            return $fromQuery;
        }

        $fromCookie = isset($_COOKIE['astro_lang']) ? strtolower((string) $_COOKIE['astro_lang']) : '';
        if (in_array($fromCookie, ['es', 'en'], true)) {
            return $fromCookie;
        }

        return 'es';
    }

    public static function persist(string $locale): void
    {
        $locale = self::normalize($locale);
        $_COOKIE['astro_lang'] = $locale;
        if (!headers_sent()) {
            setcookie('astro_lang', $locale, [
                'expires' => time() + 60 * 60 * 24 * 365,
                'path' => '/',
                'secure' => false,
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
        }
    }

    public static function normalize(string $locale): string
    {
        $locale = strtolower(trim($locale));
        return $locale === 'en' ? 'en' : 'es';
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function isSpanish(): bool
    {
        return $this->locale === 'es';
    }

    /**
     * @param array<string, scalar|null> $replace
     */
    public function t(string $key, array $replace = []): string
    {
        $text = $this->lines[$key] ?? $key;
        foreach ($replace as $name => $value) {
            $text = str_replace(':' . $name, (string) $value, $text);
        }
        return $text;
    }

    /**
     * @return array<string, string>
     */
    private static function load(string $locale): array
    {
        $path = dirname(__DIR__, 2) . '/config/lang/' . $locale . '.php';
        if (!is_readable($path)) {
            $path = dirname(__DIR__, 2) . '/config/lang/es.php';
        }
        $lines = require $path;
        return is_array($lines) ? $lines : [];
    }
}
