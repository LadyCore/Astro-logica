<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Shared birth-data form defaults + request parsing for chart pages.
 */
final class BirthForm
{
    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function defaults(array $config): array
    {
        $api = $config['astro_api'] ?? [];
        $tz = (string) ($config['timezone'] ?? 'America/Argentina/Buenos_Aires');
        $nowYear = (int) (new \DateTimeImmutable('now', new \DateTimeZone($tz)))->format('Y');

        return [
            'name' => 'Visitante',
            'year' => 1990,
            'month' => 5,
            'day' => 15,
            'hour' => 14,
            'minute' => 30,
            'time_known' => true,
            'city' => (string) ($api['sky_city'] ?? 'Buenos Aires'),
            'lat' => (float) ($api['sky_lat'] ?? -34.6037),
            'lng' => (float) ($api['sky_lng'] ?? -58.3816),
            'tz_str' => $tz,
            'house_system' => 'placidus',
            'zodiac_type' => 'tropical',
            'solar_year' => $nowYear,
            'current_city' => (string) ($api['sky_city'] ?? 'Buenos Aires'),
            'current_lat' => (float) ($api['sky_lat'] ?? -34.6037),
            'current_lng' => (float) ($api['sky_lng'] ?? -58.3816),
            'transit_date' => (new \DateTimeImmutable('now', new \DateTimeZone($tz)))->format('Y-m-d\TH:i'),
            'sign' => 'scorpio',
        ];
    }

    /**
     * @param array<string, mixed> $source
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    public static function fromRequest(array $source, array $defaults): array
    {
        $out = $defaults;
        $str = static fn (string $key): string => trim((string) ($source[$key] ?? $defaults[$key] ?? ''));
        $int = static function (string $key) use ($source, $defaults): int {
            $raw = $source[$key] ?? $defaults[$key] ?? 0;
            return (int) $raw;
        };
        $float = static function (string $key) use ($source, $defaults): float {
            $raw = $source[$key] ?? $defaults[$key] ?? 0;
            return (float) $raw;
        };

        $out['name'] = $str('name') !== '' ? $str('name') : (string) $defaults['name'];
        $out['year'] = $int('year');
        $out['month'] = max(1, min(12, $int('month')));
        $out['day'] = max(1, min(31, $int('day')));
        $out['hour'] = max(0, min(23, $int('hour')));
        $out['minute'] = max(0, min(59, $int('minute')));
        $out['time_known'] = !isset($source['time_known']) || (string) $source['time_known'] !== '0';
        $out['city'] = $str('city');
        $out['lat'] = $float('lat');
        $out['lng'] = $float('lng');
        $out['tz_str'] = $str('tz_str') !== '' ? $str('tz_str') : (string) $defaults['tz_str'];
        $out['solar_year'] = $int('solar_year') > 0 ? $int('solar_year') : (int) $defaults['solar_year'];
        $out['current_city'] = $str('current_city') !== '' ? $str('current_city') : (string) $defaults['current_city'];
        $out['current_lat'] = isset($source['current_lat']) ? $float('current_lat') : (float) $defaults['current_lat'];
        $out['current_lng'] = isset($source['current_lng']) ? $float('current_lng') : (float) $defaults['current_lng'];
        $out['transit_date'] = $str('transit_date') !== '' ? $str('transit_date') : (string) $defaults['transit_date'];
        $out['sign'] = strtolower($str('sign') !== '' ? $str('sign') : (string) $defaults['sign']);
        $out['return_city'] = $out['current_city'];
        $out['return_lat'] = $out['current_lat'];
        $out['return_lng'] = $out['current_lng'];

        return $out;
    }

    /**
     * @param array<string, mixed> $form
     * @return array<string, mixed>
     */
    public static function toBirthPayload(array $form): array
    {
        return [
            'name' => (string) $form['name'],
            'year' => (int) $form['year'],
            'month' => (int) $form['month'],
            'day' => (int) $form['day'],
            'hour' => (int) $form['hour'],
            'minute' => (int) $form['minute'],
            'time_known' => (bool) $form['time_known'],
            'city' => (string) $form['city'],
            'lat' => (float) $form['lat'],
            'lng' => (float) $form['lng'],
            'tz_str' => (string) $form['tz_str'],
            'house_system' => 'placidus',
            'zodiac_type' => 'tropical',
            'current_city' => (string) ($form['current_city'] ?? $form['city']),
            'current_lat' => (float) ($form['current_lat'] ?? $form['lat']),
            'current_lng' => (float) ($form['current_lng'] ?? $form['lng']),
            'return_city' => (string) ($form['return_city'] ?? $form['city']),
            'return_lat' => (float) ($form['return_lat'] ?? $form['lat']),
            'return_lng' => (float) ($form['return_lng'] ?? $form['lng']),
        ];
    }
}
