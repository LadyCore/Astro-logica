<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Localizes daily-horoscope payload for UI locale.
 * Provider supports en/fr/it/hi only — Spanish is translated from English.
 */
final class HoroscopeLocalizer
{
    public function __construct(
        private readonly TextTranslator $translator = new TextTranslator(),
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function localize(array $data, string $locale): array
    {
        $locale = I18n::normalize($locale);
        if ($locale !== 'es') {
            return $data;
        }

        if (isset($data['content']) && is_array($data['content'])) {
            $data['content'] = $this->localizeContent($data['content']);
        }
        if (isset($data['lucky']) && is_array($data['lucky'])) {
            $data['lucky'] = $this->localizeLucky($data['lucky']);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $content
     * @return array<string, mixed>
     */
    private function localizeContent(array $content): array
    {
        foreach (['text', 'overview', 'general', 'summary', 'theme', 'love', 'career', 'money', 'health'] as $key) {
            if (isset($content[$key]) && is_string($content[$key]) && trim($content[$key]) !== '') {
                $content[$key] = $this->translator->translate($content[$key], 'en', 'es');
            }
        }

        if (isset($content['keywords']) && is_array($content['keywords'])) {
            $content['keywords'] = array_values(array_map(
                fn ($kw) => is_string($kw) ? $this->keywordEs($kw) : $kw,
                $content['keywords']
            ));
        }

        if (isset($content['supporting_insights']) && is_array($content['supporting_insights'])) {
            $content['supporting_insights'] = array_values(array_map(
                fn ($line) => is_string($line) ? $this->translator->translate($line, 'en', 'es') : $line,
                $content['supporting_insights']
            ));
        }

        // Theme dictionary polish for short labels.
        if (isset($content['theme']) && is_string($content['theme'])) {
            $content['theme'] = $this->themeEs($content['theme']);
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $lucky
     * @return array<string, mixed>
     */
    private function localizeLucky(array $lucky): array
    {
        if (isset($lucky['color'])) {
            if (is_string($lucky['color'])) {
                $lucky['color'] = $this->colorEs($lucky['color']);
            } elseif (is_array($lucky['color'])) {
                $joined = implode(', ', array_map('strval', $lucky['color']));
                $lucky['color'] = $this->colorEs($joined);
            }
        }
        if (isset($lucky['time']) && is_string($lucky['time'])) {
            $lucky['time'] = $this->translator->translate($lucky['time'], 'en', 'es');
        }
        return $lucky;
    }

    private function themeEs(string $theme): string
    {
        $key = strtolower(trim($theme));
        $map = $this->keywordMap();
        if (isset($map[$key])) {
            return $map[$key];
        }
        return $theme;
    }

    private function keywordEs(string $keyword): string
    {
        $key = strtolower(trim($keyword));
        $map = $this->keywordMap();
        if (isset($map[$key])) {
            return $map[$key];
        }
        return $this->translator->translate($keyword, 'en', 'es');
    }

    /**
     * @return array<string, string>
     */
    private function keywordMap(): array
    {
        return [
            'stability' => 'Estabilidad',
            'intensity' => 'Intensidad',
            'confidence' => 'Confianza',
            'clarity' => 'Claridad',
            'balance' => 'Equilibrio',
            'growth' => 'Crecimiento',
            'focus' => 'Enfoque',
            'creativity' => 'Creatividad',
            'leadership' => 'Liderazgo',
            'sensuality' => 'Sensualidad',
            'practicality' => 'Practicidad',
            'mystery' => 'Misterio',
            'transformation' => 'Transformación',
            'harmony' => 'Armonía',
            'courage' => 'Coraje',
            'patience' => 'Paciencia',
            'intuition' => 'Intuición',
            'ambition' => 'Ambición',
            'healing' => 'Sanación',
            'connection' => 'Conexión',
            'sense' => 'Sentido',
            'meaning' => 'Sentido',
        ];
    }

    private function colorEs(string $color): string
    {
        $parts = preg_split('/\s*,\s*/', $color) ?: [$color];
        $map = [
            'red' => 'rojo',
            'blue' => 'azul',
            'green' => 'verde',
            'yellow' => 'amarillo',
            'orange' => 'naranja',
            'purple' => 'violeta',
            'violet' => 'violeta',
            'pink' => 'rosa',
            'gold' => 'dorado',
            'silver' => 'plateado',
            'white' => 'blanco',
            'black' => 'negro',
            'brown' => 'marrón',
            'beige' => 'beige',
            'turquoise' => 'turquesa',
            'indigo' => 'índigo',
        ];
        $out = [];
        $seen = [];
        foreach ($parts as $part) {
            $raw = trim((string) $part);
            if ($raw === '') {
                continue;
            }
            $key = strtolower($raw);
            $es = $map[$key] ?? $this->translator->translate($raw, 'en', 'es');
            $norm = mb_strtolower($es);
            if (isset($seen[$norm])) {
                continue;
            }
            $seen[$norm] = true;
            $out[] = $es;
        }
        return implode(', ', $out);
    }
}
