<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\DailySky;
use App\Models\LunarPhase;

/**
 * Forces horoscope copy to cite the same live Moon sign + phase as Cielo del día.
 *
 * FreeAstroAPI daily-sign text often invents its own “Moon in …” / phase labels;
 * those must not contradict AstroService::getDailySky().
 */
final class LiveSkyAligner
{
    /**
     * @param array<string, mixed> $horoscope Localized (or EN) daily-sign payload
     * @return array<string, mixed>
     */
    public function align(array $horoscope, DailySky $sky, string $locale = 'es'): array
    {
        $locale = I18n::normalize($locale);
        $moon = $sky->body('Moon');
        $signId = $moon ? strtolower($moon->signId) : 'cancer';
        $signName = PlanetMeanings::signName($signId, $locale);
        $phase = $sky->lunarPhase;
        $phaseLabel = $this->phaseLabel($phase, $locale);
        $moonTheme = $locale === 'en'
            ? ('Moon in ' . $signName)
            : ('Luna en ' . $signName);

        $horoscope['sky'] = [
            'moment' => $sky->moment->format(\DateTimeInterface::ATOM),
            'source' => $sky->source,
            'moon_sign_id' => $signId,
            'moon_sign' => $signName,
            'moon_phase' => $phaseLabel,
            'moon_phase_slug' => $phase->slug,
            'moon_theme' => $moonTheme,
        ];

        if (!isset($horoscope['content']) || !is_array($horoscope['content'])) {
            $horoscope['content'] = [];
        }
        $content = $horoscope['content'];
        $content['theme'] = $moonTheme;
        $content['moon_phase'] = $phaseLabel;

        $signNameEn = PlanetMeanings::signName($signId, 'en');
        $phaseLabelEn = $this->phaseLabel($phase, 'en');

        foreach (['text', 'overview', 'general', 'summary', 'love', 'career', 'money', 'health'] as $key) {
            if (isset($content[$key]) && is_string($content[$key]) && $content[$key] !== '') {
                $content[$key] = $this->rewriteSkyMentions(
                    $content[$key],
                    $signName,
                    $signNameEn,
                    $phaseLabel,
                    $phaseLabelEn
                );
            }
        }
        if (isset($content['supporting_insights']) && is_array($content['supporting_insights'])) {
            $content['supporting_insights'] = array_map(
                function ($line) use ($signName, $signNameEn, $phaseLabel, $phaseLabelEn) {
                    return is_string($line)
                        ? $this->rewriteSkyMentions($line, $signName, $signNameEn, $phaseLabel, $phaseLabelEn)
                        : $line;
                },
                $content['supporting_insights']
            );
        }

        $horoscope['content'] = $content;
        return $horoscope;
    }

    private function phaseLabel(LunarPhase $phase, string $locale): string
    {
        if ($locale === 'en') {
            return $phase->name !== '' ? $phase->name : $this->phaseNameFromSlug($phase->slug, 'en');
        }

        // Keep labels identical to Cielo del día (hoy/index.php).
        $es = [
            'new-moon' => 'Luna Nueva',
            'waxing-crescent' => 'Luna Creciente',
            'first-quarter' => 'Cuarto Creciente',
            'waxing-gibbous' => 'Gibosa Creciente',
            'full-moon' => 'Luna Llena',
            'waning-gibbous' => 'Gibosa Menguante',
            'last-quarter' => 'Cuarto Menguante',
            'waning-crescent' => 'Luna Menguante',
        ];
        return $es[$phase->slug] ?? $this->phaseNameFromSlug($phase->slug, 'es');
    }

    private function phaseNameFromSlug(string $slug, string $locale): string
    {
        $en = [
            'new-moon' => 'New Moon',
            'waxing-crescent' => 'Waxing Crescent',
            'first-quarter' => 'First Quarter',
            'waxing-gibbous' => 'Waxing Gibbous',
            'full-moon' => 'Full Moon',
            'waning-gibbous' => 'Waning Gibbous',
            'last-quarter' => 'Last Quarter',
            'waning-crescent' => 'Waning Crescent',
        ];
        $name = $en[$slug] ?? ucwords(str_replace('-', ' ', $slug));
        if ($locale === 'en') {
            return $name;
        }
        // Fallback ES via known map in phaseLabel — if unknown slug, keep English name lightly tagged.
        return $name;
    }

    private function rewriteSkyMentions(
        string $text,
        string $signNameEs,
        string $signNameEn,
        string $phaseLabelEs,
        string $phaseLabelEn,
    ): string {
        // Keep phrase language: EN stays EN, ES stays ES (avoid "With the Luna en …").
        $text = preg_replace(
            '/\bMoon\s+in\s+[A-Za-zÁÉÍÓÚáéíóúüñÑ]+/iu',
            'Moon in ' . $signNameEn,
            $text
        ) ?? $text;
        $text = preg_replace(
            '/\bLuna\s+en\s+[A-Za-zÁÉÍÓÚáéíóúüñÑ]+/iu',
            'Luna en ' . $signNameEs,
            $text
        ) ?? $text;

        $enPhases = [
            'Waxing Gibbous', 'Waning Gibbous', 'Waxing Crescent', 'Waning Crescent',
            'First Quarter', 'Last Quarter', 'Full Moon', 'New Moon',
        ];
        $esPhases = [
            'Gibosa Creciente', 'Gibosa Menguante', 'Cuarto Creciente', 'Cuarto Menguante',
            'Luna Creciente', 'Luna Menguante', 'Luna Llena', 'Luna Nueva',
        ];
        usort($enPhases, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        usort($esPhases, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        foreach ($enPhases as $pattern) {
            if (strcasecmp($pattern, $phaseLabelEn) === 0) {
                continue;
            }
            $text = preg_replace('/\b' . preg_quote($pattern, '/') . '\b/iu', $phaseLabelEn, $text) ?? $text;
        }
        foreach ($esPhases as $pattern) {
            if (strcasecmp($pattern, $phaseLabelEs) === 0) {
                continue;
            }
            $text = preg_replace('/\b' . preg_quote($pattern, '/') . '\b/iu', $phaseLabelEs, $text) ?? $text;
        }

        return $text;
    }
}
