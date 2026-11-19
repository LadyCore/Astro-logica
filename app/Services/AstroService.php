<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailySky;
use App\Models\LunarPhase;
use App\Models\NatalChart;
use App\Models\PlanetPosition;
use RuntimeException;

/**
 * FreeAstroAPI client (ephemeris + natal).
 *
 * Lives in app/Services (project convention; not src/Services).
 * Controllers pass the app config array: new AstroService($this->config).
 * Also accepts a bare API key string: new AstroService($apiKey).
 */
final class AstroService
{
    private string $apiKey;

    private string $baseUrl;

    /** @var array<string, mixed> */
    private array $config;

    /**
     * @param array<string, mixed>|string $configOrApiKey App config or raw API key
     */
    public function __construct(array|string $configOrApiKey)
    {
        if (is_string($configOrApiKey)) {
            $this->apiKey = $configOrApiKey;
            $this->baseUrl = 'https://api.freeastroapi.com';
            $this->config = [
                'timezone' => date_default_timezone_get() ?: 'UTC',
                'debug' => false,
                'astro_api' => [
                    'base_url' => $this->baseUrl,
                    'api_key' => $this->apiKey,
                    'timeout' => 15,
                    'ephemeris_path' => '/api/v1/ephemeris/calculate',
                    'natal_path' => '/api/v1/natal/calculate',
                    'bodies' => [
                        'Sun', 'Moon', 'Mercury', 'Venus', 'Mars',
                        'Jupiter', 'Saturn', 'Uranus', 'Neptune', 'Pluto',
                    ],
                ],
            ];
            return;
        }

        $this->config = $configOrApiKey;
        $api = $configOrApiKey['astro_api'] ?? [];
        $this->apiKey = is_string($api['api_key'] ?? null) ? (string) $api['api_key'] : '';
        $this->baseUrl = rtrim((string) ($api['base_url'] ?? 'https://api.freeastroapi.com'), '/');
    }

    public function hasApiKey(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * Daily sky for controllers/views (typed model + mock fallback).
     * Pass a Y-m-d string or DateTimeImmutable; null = now in app timezone.
     */
    public function getDailySky(\DateTimeImmutable|string|null $date = null): DailySky
    {
        $tz = new \DateTimeZone((string) ($this->config['timezone'] ?? 'UTC'));
        if ($date instanceof \DateTimeImmutable) {
            $moment = $date;
        } elseif (is_string($date) && $date !== '') {
            $moment = new \DateTimeImmutable($date . ' 12:00:00', $tz);
        } else {
            $moment = new \DateTimeImmutable('now', $tz);
        }

        if (!$this->hasApiKey()) {
            return $this->mockDailySky($moment);
        }

        try {
            return $this->fetchDailySkyFromApi($moment);
        } catch (\Throwable $e) {
            if (!empty($this->config['debug'])) {
                error_log('[AstroService] ephemeris failed, using mock: ' . $e->getMessage());
            }
            $sky = $this->mockDailySky($moment);
            return new DailySky(
                moment: $sky->moment,
                bodies: $sky->bodies,
                lunarPhase: $sky->lunarPhase,
                source: 'mock-fallback',
                meta: array_merge($sky->meta, [
                    'error' => $e->getMessage(),
                    'note' => 'API call failed; showing mock sky.',
                ]),
            );
        }
    }

    /**
     * Raw ephemeris snapshot (FreeAstroAPI JSON) or error array.
     *
     * @return array<string, mixed>
     */
    public function getDailySkyRaw(?string $date = null): array
    {
        $tz = new \DateTimeZone((string) ($this->config['timezone'] ?? 'UTC'));
        if ($date !== null && $date !== '') {
            $moment = new \DateTimeImmutable($date . ' 12:00:00', $tz);
        } else {
            $moment = new \DateTimeImmutable('now', $tz);
        }

        $api = $this->config['astro_api'] ?? [];
        $path = (string) ($api['ephemeris_path'] ?? '/api/v1/ephemeris/calculate');
        $bodiesList = $api['bodies'] ?? [
            'Sun', 'Moon', 'Mercury', 'Venus', 'Mars',
            'Jupiter', 'Saturn', 'Uranus', 'Neptune', 'Pluto',
        ];

        return $this->request($path, $this->ephemerisPayload($moment, $bodiesList));
    }

    /**
     * Natal chart calculation.
     *
     * Accepts either:
     * - Simple: date, time, latitude, longitude [, house_system]
     * - Rich: year, month, day, city, hour, minute, lat, lng, …
     *
     * @param array<string, mixed> $birthData
     */
    public function getNatalChart(array $birthData): NatalChart
    {
        if (!$this->hasApiKey()) {
            throw new RuntimeException(
                'Todavía no está configurada la conexión al cielo en vivo.'
            );
        }

        $payload = $this->normalizeNatalPayload($birthData);
        $path = (string) ($this->config['astro_api']['natal_path'] ?? '/api/v1/natal/calculate');
        $raw = $this->request($path, $payload);

        if (!empty($raw['error'])) {
            $msg = is_string($raw['message'] ?? null) ? $raw['message'] : 'Natal API error';
            throw new RuntimeException($msg . (isset($raw['http_code']) ? ' (HTTP ' . $raw['http_code'] . ')' : ''));
        }

        return new NatalChart(payload: $raw, source: 'api');
    }

    /**
     * Raw natal call — same payload rules as getNatalChart, returns array.
     *
     * @param array<string, mixed> $birthData
     * @return array<string, mixed>
     */
    public function getNatalChartRaw(array $birthData): array
    {
        $path = (string) ($this->config['astro_api']['natal_path'] ?? '/api/v1/natal/calculate');
        return $this->request($path, $this->normalizeNatalPayload($birthData));
    }

    /**
     * SVG wheel for the current sky (uses natal/chart with "now" as the moment).
     * Default visual: FreeAstroAPI editorial preset "Ivory Ink" (light paper + ink).
     * Returns null when there is no API key or the request fails (caller keeps fallback art).
     *
     * @param string $themePreset ivory-ink|dark (ivory-ink is the Hoy default)
     */
    public function getDailySkyChartSvg(?\DateTimeImmutable $moment = null, string $themePreset = 'ivory-ink'): ?string
    {
        if (!$this->hasApiKey()) {
            return null;
        }

        $tz = new \DateTimeZone((string) ($this->config['timezone'] ?? 'UTC'));
        $moment ??= new \DateTimeImmutable('now', $tz);
        $preset = strtolower($themePreset);
        if ($preset === 'light' || $preset === 'ivory' || $preset === 'ivory_ink') {
            $preset = 'ivory-ink';
        }
        if ($preset !== 'dark' && $preset !== 'ivory-ink') {
            $preset = 'ivory-ink';
        }

        $cacheSeconds = (int) ($this->config['astro_api']['chart_cache_seconds'] ?? 900);
        $bucket = (int) floor(((int) $moment->format('i')) / 15) * 15;
        $cacheKey = sprintf(
            'sky-chart-%s-%s-%02d.svg',
            $preset,
            $moment->format('YmdH'),
            $bucket
        );
        $cached = $this->readChartCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $api = $this->config['astro_api'] ?? [];
        $path = (string) ($api['chart_svg_path'] ?? '/api/v1/natal/chart/');
        $themeType = $preset === 'dark' ? 'dark' : 'light';
        $chartConfig = $preset === 'dark'
            ? [
                'chart_background' => '#010c1d',
                'aspect_circle_background' => '#06101c',
                'custom_planet_color' => '#e4c48a',
                'custom_sign_color' => '#c9a46a',
                'custom_house_color' => '#9aa8bb',
                'custom_sign_bg_color' => '#0a1422',
                'custom_house_bg_color' => '#06101c',
                'planet_color_mode' => 'sign',
                'show_retrograde_markers' => true,
                'retrograde_marker_style' => 'rx',
                'show_aspect_symbols' => false,
                'planet_symbol_scale' => 0.38,
                'sign_symbol_scale' => 0.55,
            ]
            // FreeAstroAPI editorial preset: Ivory Ink
            : [
                'chart_background' => '#F6F1E8',
                'aspect_circle_background' => '#FAF6EE',
                'custom_planet_color' => '#1E1B18',
                'custom_sign_color' => '#6A5B48',
                'custom_house_color' => '#3E362C',
                'custom_sign_bg_color' => '#EFE4D3',
                'custom_house_bg_color' => '#FAF6EE',
                'sign_line_color' => '#3A3128',
                'house_line_color' => '#8C7C68',
                'aspect_conjunction_color' => '#2F2A24',
                'aspect_opposition_color' => '#A1362A',
                'aspect_trine_color' => '#1F4E79',
                'aspect_square_color' => '#8F2B23',
                'aspect_sextile_color' => '#355E8D',
                'aspect_quincunx_color' => '#4D6A3A',
                'show_retrograde_markers' => true,
                'retrograde_marker_style' => 'rx',
                'show_aspect_symbols' => false,
                'planet_symbol_scale' => 0.38,
                'sign_symbol_scale' => 0.55,
            ];

        $payload = [
            'name' => 'Cielo del día',
            'year' => (int) $moment->format('Y'),
            'month' => (int) $moment->format('n'),
            'day' => (int) $moment->format('j'),
            'hour' => (int) $moment->format('G'),
            'minute' => (int) $moment->format('i'),
            'time_known' => true,
            'city' => (string) ($api['sky_city'] ?? 'Buenos Aires'),
            'lat' => (float) ($api['sky_lat'] ?? -34.6037),
            'lng' => (float) ($api['sky_lng'] ?? -58.3816),
            'tz_str' => (string) ($this->config['timezone'] ?? 'America/Argentina/Buenos_Aires'),
            'house_system' => 'placidus',
            'zodiac_type' => 'tropical',
            'format' => 'svg',
            'size' => 720,
            'theme_type' => $themeType,
            'show_metadata' => false,
            'display_settings' => [
                'sun' => true,
                'moon' => true,
                'mercury' => true,
                'venus' => true,
                'mars' => true,
                'jupiter' => true,
                'saturn' => true,
                'uranus' => true,
                'neptune' => true,
                'pluto' => true,
                'chiron' => false,
                'north_node' => true,
                'lilith' => false,
            ],
            'chart_config' => $chartConfig,
        ];

        try {
            $svg = $this->requestSvg($path, $payload);
        } catch (\Throwable $e) {
            if (!empty($this->config['debug'])) {
                error_log('[AstroService] sky chart SVG failed: ' . $e->getMessage());
            }
            return null;
        }

        if ($svg === null || $svg === '') {
            return null;
        }

        $this->writeChartCache($cacheKey, $svg, $cacheSeconds);
        return $svg;
    }

    /**
     * Birth Chart SVG (FreeAstroAPI natal/chart) — Ivory Ink by default.
     *
     * @param array<string, mixed> $birthData
     */
    public function getBirthChartSvg(array $birthData, string $themePreset = 'ivory-ink'): ?string
    {
        if (!$this->hasApiKey()) {
            return null;
        }

        $natal = $this->normalizeNatalPayload($birthData);
        $preset = $this->normalizeThemePreset($themePreset);
        $cacheKey = 'birth-' . md5(json_encode([$natal, $preset], JSON_THROW_ON_ERROR)) . '.svg';
        $cached = $this->readChartCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $path = (string) ($this->config['astro_api']['chart_svg_path'] ?? '/api/v1/natal/chart/');
        $payload = array_merge($natal, $this->svgRenderOptions($preset), [
            'show_metadata' => false,
            'display_settings' => $this->defaultDisplaySettings(),
            'chart_config' => $this->chartConfigForPreset($preset),
        ]);

        try {
            $svg = $this->requestSvg($path, $payload);
        } catch (\Throwable $e) {
            if (!empty($this->config['debug'])) {
                error_log('[AstroService] birth chart SVG failed: ' . $e->getMessage());
            }
            return null;
        }

        if ($svg === '') {
            return null;
        }

        $ttl = (int) ($this->config['astro_api']['chart_cache_seconds'] ?? 900);
        $this->writeChartCache($cacheKey, $svg, $ttl);
        return $svg;
    }

    /**
     * Solar Return chart SVG — FreeAstroAPI /api/v1/natal/chart/solar-return
     *
     * @param array<string, mixed> $birthData
     */
    public function getSolarReturnChartSvg(array $birthData, int $year, string $themePreset = 'ivory-ink'): ?string
    {
        if (!$this->hasApiKey()) {
            return null;
        }

        $natal = $this->normalizeNatalPayload($birthData);
        $preset = $this->normalizeThemePreset($themePreset);
        $api = $this->config['astro_api'] ?? [];
        $returnCity = (string) ($birthData['return_city'] ?? $natal['city'] ?? $api['sky_city'] ?? 'Buenos Aires');
        $cacheKey = 'solar-' . md5(json_encode([$natal, $year, $returnCity, $preset], JSON_THROW_ON_ERROR)) . '.svg';
        $cached = $this->readChartCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $path = (string) ($api['solar_return_svg_path'] ?? '/api/v1/natal/chart/solar-return');
        $render = $this->svgRenderOptions($preset);
        $payload = [
            'natal' => array_merge($natal, $render, [
                'show_metadata' => false,
                'display_settings' => $this->defaultDisplaySettings(),
                'chart_config' => $this->chartConfigForPreset($preset),
            ]),
            'solar_return_year' => $year,
            'chart_mode' => 'solar_return_only',
            'solar_return_city' => $returnCity,
            'solar_return_tz_str' => (string) ($natal['tz_str'] ?? $this->config['timezone'] ?? 'AUTO'),
            'show_solar_return_aspects' => true,
        ];
        if (isset($birthData['return_lat'], $birthData['return_lng'])) {
            $payload['solar_return_lat'] = (float) $birthData['return_lat'];
            $payload['solar_return_lng'] = (float) $birthData['return_lng'];
        } elseif (isset($natal['lat'], $natal['lng'])) {
            $payload['solar_return_lat'] = (float) $natal['lat'];
            $payload['solar_return_lng'] = (float) $natal['lng'];
        }

        try {
            $svg = $this->requestSvg($path, $payload);
        } catch (\Throwable $e) {
            if (!empty($this->config['debug'])) {
                error_log('[AstroService] solar return SVG failed: ' . $e->getMessage());
            }
            return null;
        }

        if ($svg === '') {
            return null;
        }

        $ttl = (int) ($api['chart_cache_seconds'] ?? 900);
        $this->writeChartCache($cacheKey, $svg, $ttl);
        return $svg;
    }

    /**
     * Transit Chart SVG — FreeAstroAPI /api/v1/natal/chart/transits
     *
     * @param array<string, mixed> $birthData
     */
    public function getTransitChartSvg(
        array $birthData,
        ?string $transitDate = null,
        string $themePreset = 'ivory-ink',
    ): ?string {
        if (!$this->hasApiKey()) {
            return null;
        }

        $natal = $this->normalizeNatalPayload($birthData);
        $preset = $this->normalizeThemePreset($themePreset);
        $api = $this->config['astro_api'] ?? [];
        $tz = (string) ($this->config['timezone'] ?? 'America/Argentina/Buenos_Aires');
        $when = $transitDate && $transitDate !== ''
            ? $transitDate
            : (new \DateTimeImmutable('now', new \DateTimeZone($tz)))->format('Y-m-d\TH:i');
        $currentCity = (string) ($birthData['current_city'] ?? $api['sky_city'] ?? 'Buenos Aires');
        $cacheKey = 'transit-' . md5(json_encode([$natal, $when, $currentCity, $preset], JSON_THROW_ON_ERROR)) . '.svg';
        $cached = $this->readChartCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $path = (string) ($api['transit_svg_path'] ?? '/api/v1/natal/chart/transits');
        $render = $this->svgRenderOptions($preset);
        $payload = [
            'natal' => array_merge($natal, $render, [
                'show_metadata' => false,
                'display_settings' => $this->defaultDisplaySettings(),
                'chart_config' => $this->chartConfigForPreset($preset),
            ]),
            'current_city' => $currentCity,
            'transit_date' => $when,
            'tz_str' => $tz,
            'show_inter_aspects' => true,
            'show_natal_aspects' => false,
            'show_transit_aspects' => false,
        ];
        if (isset($birthData['current_lat'], $birthData['current_lng'])) {
            $payload['current_lat'] = (float) $birthData['current_lat'];
            $payload['current_lng'] = (float) $birthData['current_lng'];
        } elseif (isset($api['sky_lat'], $api['sky_lng'])) {
            $payload['current_lat'] = (float) $api['sky_lat'];
            $payload['current_lng'] = (float) $api['sky_lng'];
        }

        try {
            $svg = $this->requestSvg($path, $payload);
        } catch (\Throwable $e) {
            if (!empty($this->config['debug'])) {
                error_log('[AstroService] transit chart SVG failed: ' . $e->getMessage());
            }
            return null;
        }

        if ($svg === '') {
            return null;
        }

        $ttl = (int) ($api['chart_cache_seconds'] ?? 900);
        $this->writeChartCache($cacheKey, $svg, $ttl);
        return $svg;
    }

    /**
     * Solar Return JSON (planets/houses) — /api/v1/western/solar/calculate
     *
     * @param array<string, mixed> $birthData
     * @return array<string, mixed>
     */
    public function getSolarReturnData(array $birthData, int $year): array
    {
        if (!$this->hasApiKey()) {
            return ['error' => true, 'message' => 'Falta configurar la clave del cielo en vivo.'];
        }

        $natal = $this->normalizeNatalPayload($birthData);
        $api = $this->config['astro_api'] ?? [];
        $tz = (string) ($natal['tz_str'] ?? $this->config['timezone'] ?? 'AUTO');
        $city = (string) ($birthData['return_city'] ?? $natal['city'] ?? $api['sky_city'] ?? 'Buenos Aires');
        $lat = (float) ($birthData['return_lat'] ?? $natal['lat'] ?? $api['sky_lat'] ?? -34.6037);
        $lng = (float) ($birthData['return_lng'] ?? $natal['lng'] ?? $api['sky_lng'] ?? -58.3816);

        $path = (string) ($api['solar_return_path'] ?? '/api/v1/western/solar/calculate');
        $payload = [
            'natal' => [
                'name' => (string) ($natal['name'] ?? 'Subject'),
                'year' => (int) $natal['year'],
                'month' => (int) $natal['month'],
                'day' => (int) $natal['day'],
                'hour' => (int) ($natal['hour'] ?? 12),
                'minute' => (int) ($natal['minute'] ?? 0),
                'time_known' => (bool) ($natal['time_known'] ?? true),
                'location' => [
                    'city' => (string) ($natal['city'] ?? $city),
                    'lat' => (float) ($natal['lat'] ?? $lat),
                    'lng' => (float) ($natal['lng'] ?? $lng),
                    'timezone' => $tz,
                ],
            ],
            'solar_return' => [
                'year' => $year,
                'location' => [
                    'city' => $city,
                    'lat' => $lat,
                    'lng' => $lng,
                    'timezone' => $tz,
                ],
            ],
        ];

        return $this->request($path, $payload);
    }

    /**
     * Transit-to-natal JSON — /api/v1/transits/calculate
     *
     * @param array<string, mixed> $birthData
     * @return array<string, mixed>
     */
    public function getTransitsData(array $birthData, ?string $transitDate = null): array
    {
        if (!$this->hasApiKey()) {
            return ['error' => true, 'message' => 'Falta configurar la clave del cielo en vivo.'];
        }

        $natal = $this->normalizeNatalPayload($birthData);
        $api = $this->config['astro_api'] ?? [];
        $tz = (string) ($this->config['timezone'] ?? 'America/Argentina/Buenos_Aires');
        $when = $transitDate && $transitDate !== ''
            ? $transitDate
            : (new \DateTimeImmutable('now', new \DateTimeZone($tz)))->format('Y-m-d\TH:i');

        $path = (string) ($api['transits_path'] ?? '/api/v1/transits/calculate');
        $payload = [
            'natal' => [
                'name' => (string) ($natal['name'] ?? 'Subject'),
                'year' => (int) $natal['year'],
                'month' => (int) $natal['month'],
                'day' => (int) $natal['day'],
                'hour' => (int) ($natal['hour'] ?? 12),
                'minute' => (int) ($natal['minute'] ?? 0),
                'time_known' => (bool) ($natal['time_known'] ?? true),
                'city' => (string) ($natal['city'] ?? ''),
                'lat' => (float) ($natal['lat'] ?? $api['sky_lat'] ?? -34.6037),
                'lng' => (float) ($natal['lng'] ?? $api['sky_lng'] ?? -58.3816),
                'tz_str' => (string) ($natal['tz_str'] ?? $tz),
            ],
            'current_city' => (string) ($birthData['current_city'] ?? $api['sky_city'] ?? 'Buenos Aires'),
            'current_lat' => (float) ($birthData['current_lat'] ?? $api['sky_lat'] ?? -34.6037),
            'current_lng' => (float) ($birthData['current_lng'] ?? $api['sky_lng'] ?? -58.3816),
            'transit_date' => $when,
            'tz_str' => $tz,
            'interpretation' => ['enable' => false],
        ];

        return $this->request($path, $payload);
    }

    /**
     * Daily Horoscope by sun sign — FreeAstroAPI /api/v2/horoscope/daily/sign
     *
     * @return array<string, mixed>
     */
    public function getDailySignHoroscope(string $sign, ?string $date = null, string $lang = 'en'): array
    {
        if (!$this->hasApiKey()) {
            return ['error' => true, 'message' => 'Falta FREE_ASTRO_API_KEY'];
        }

        $sign = strtolower(trim($sign));
        $path = (string) ($this->config['astro_api']['daily_sign_path'] ?? '/api/v2/horoscope/daily/sign');
        $tz = (string) ($this->config['timezone'] ?? 'America/Argentina/Buenos_Aires');
        $query = [
            'sign' => $sign,
            'date' => $date ?: 'today',
            'tz_str' => $tz,
            'lang' => $lang,
        ];

        $result = $this->curlGet($path, $query, 'application/json');
        if ($result['http_code'] !== 200 || $result['body'] === '') {
            $detail = '';
            if ($result['body'] !== '') {
                $decoded = json_decode($result['body'], true);
                if (is_array($decoded)) {
                    $detail = is_string($decoded['detail'] ?? null)
                        ? (string) $decoded['detail']
                        : (is_string($decoded['message'] ?? null) ? (string) $decoded['message'] : '');
                }
            }
            $base = $result['error'] !== '' ? $result['error'] : 'Error al consultar el horóscopo';
            if ($result['http_code'] === 429) {
                $base = 'La API alcanzó el límite de ritmo (≈1 req/s). Esperá un momento y reintentá.';
            }
            return [
                'error' => true,
                'message' => trim($base . ($detail !== '' ? ': ' . $detail : '') . ' (HTTP ' . $result['http_code'] . ')'),
                'http_code' => $result['http_code'],
            ];
        }

        $data = json_decode($result['body'], true);
        return is_array($data)
            ? $data
            : ['error' => true, 'message' => 'Respuesta inválida'];
    }

    /**
     * @return array<string, mixed>
     */
    private function svgRenderOptions(string $preset): array
    {
        return [
            'format' => 'svg',
            'size' => 720,
            'theme_type' => $preset === 'dark' ? 'dark' : 'light',
            'house_system' => 'placidus',
            'zodiac_type' => 'tropical',
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function defaultDisplaySettings(): array
    {
        return [
            'sun' => true,
            'moon' => true,
            'mercury' => true,
            'venus' => true,
            'mars' => true,
            'jupiter' => true,
            'saturn' => true,
            'uranus' => true,
            'neptune' => true,
            'pluto' => true,
            'chiron' => false,
            'north_node' => true,
            'lilith' => false,
        ];
    }

    private function normalizeThemePreset(string $themePreset): string
    {
        $preset = strtolower($themePreset);
        if (in_array($preset, ['light', 'ivory', 'ivory_ink', 'ivory-ink'], true)) {
            return 'ivory-ink';
        }
        return $preset === 'dark' ? 'dark' : 'ivory-ink';
    }

    /**
     * @return array<string, mixed>
     */
    private function chartConfigForPreset(string $preset): array
    {
        if ($preset === 'dark') {
            return [
                'chart_background' => '#010c1d',
                'aspect_circle_background' => '#06101c',
                'custom_planet_color' => '#e4c48a',
                'custom_sign_color' => '#c9a46a',
                'custom_house_color' => '#9aa8bb',
                'custom_sign_bg_color' => '#0a1422',
                'custom_house_bg_color' => '#06101c',
                'planet_color_mode' => 'sign',
                'show_retrograde_markers' => true,
                'retrograde_marker_style' => 'rx',
                'show_aspect_symbols' => false,
                'planet_symbol_scale' => 0.38,
                'sign_symbol_scale' => 0.55,
            ];
        }

        // FreeAstroAPI editorial preset: Ivory Ink
        return [
            'chart_background' => '#F6F1E8',
            'aspect_circle_background' => '#FAF6EE',
            'custom_planet_color' => '#1E1B18',
            'custom_sign_color' => '#6A5B48',
            'custom_house_color' => '#3E362C',
            'custom_sign_bg_color' => '#EFE4D3',
            'custom_house_bg_color' => '#FAF6EE',
            'sign_line_color' => '#3A3128',
            'house_line_color' => '#8C7C68',
            'aspect_conjunction_color' => '#2F2A24',
            'aspect_opposition_color' => '#A1362A',
            'aspect_trine_color' => '#1F4E79',
            'aspect_square_color' => '#8F2B23',
            'aspect_sextile_color' => '#355E8D',
            'aspect_quincunx_color' => '#4D6A3A',
            'show_retrograde_markers' => true,
            'retrograde_marker_style' => 'rx',
            'show_aspect_symbols' => false,
            'planet_symbol_scale' => 0.38,
            'sign_symbol_scale' => 0.55,
        ];
    }

    /**
     * @param array<string, scalar|null> $query
     * @return array{http_code:int,body:string,error:string}
     */
    private function curlGet(string $endpoint, array $query, string $accept): array
    {
        $url = str_starts_with($endpoint, 'http')
            ? $endpoint
            : $this->baseUrl . $endpoint;
        $qs = http_build_query($query);
        if ($qs !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . $qs;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return ['http_code' => 0, 'body' => '', 'error' => 'No se pudo iniciar cURL'];
        }

        $timeout = (int) ($this->config['astro_api']['timeout'] ?? 20);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                'Accept: ' . $accept,
                'x-api-key: ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => $timeout,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'body' => is_string($response) ? $response : '',
            'error' => $curlError,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function request(string $endpoint, array $payload): array
    {
        $result = $this->curlPost($endpoint, $payload, 'application/json');
        if ($result['http_code'] !== 200 || $result['body'] === '') {
            return [
                'error' => true,
                'message' => $result['error'] !== '' ? $result['error'] : 'Error al consultar la API',
                'http_code' => $result['http_code'],
            ];
        }

        $data = json_decode($result['body'], true);

        return is_array($data)
            ? $data
            : ['error' => true, 'message' => 'Respuesta inválida', 'http_code' => $result['http_code']];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requestSvg(string $endpoint, array $payload): string
    {
        $result = $this->curlPost($endpoint, $payload, 'image/svg+xml, application/json');
        if ($result['http_code'] !== 200 || $result['body'] === '') {
            throw new RuntimeException(
                ($result['error'] !== '' ? $result['error'] : 'Chart SVG request failed')
                . ' (HTTP ' . $result['http_code'] . ')'
            );
        }

        $body = $result['body'];
        if (str_contains($body, '<svg')) {
            // Strip XML declaration / scripts if present; keep the SVG fragment.
            if (preg_match('/<svg\b.*<\/svg>/is', $body, $m)) {
                return $m[0];
            }
            return $body;
        }

        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            foreach (['svg', 'data', 'chart'] as $key) {
                if (isset($decoded[$key]) && is_string($decoded[$key]) && str_contains($decoded[$key], '<svg')) {
                    return $decoded[$key];
                }
            }
            $msg = $decoded['message'] ?? $decoded['error'] ?? 'Unexpected chart response';
            throw new RuntimeException(is_string($msg) ? $msg : 'Unexpected chart response');
        }

        throw new RuntimeException('Chart endpoint did not return SVG.');
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{http_code:int,body:string,error:string}
     */
    private function curlPost(string $endpoint, array $payload, string $accept): array
    {
        $url = str_starts_with($endpoint, 'http')
            ? $endpoint
            : $this->baseUrl . $endpoint;

        $ch = curl_init($url);
        if ($ch === false) {
            return ['http_code' => 0, 'body' => '', 'error' => 'No se pudo iniciar cURL'];
        }

        $timeout = (int) ($this->config['astro_api']['timeout'] ?? 20);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: ' . $accept,
                'x-api-key: ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_TIMEOUT => $timeout,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'body' => is_string($response) ? $response : '',
            'error' => $curlError,
        ];
    }

    private function chartCacheDir(): string
    {
        return dirname(__DIR__, 2) . '/storage/cache';
    }

    private function readChartCache(string $fileName): ?string
    {
        $path = $this->chartCacheDir() . '/' . $fileName;
        if (!is_readable($path)) {
            return null;
        }
        $ttl = (int) ($this->config['astro_api']['chart_cache_seconds'] ?? 900);
        if ($ttl > 0 && (time() - (int) filemtime($path)) > $ttl) {
            return null;
        }
        $svg = file_get_contents($path);
        return is_string($svg) && str_contains($svg, '<svg') ? $svg : null;
    }

    private function writeChartCache(string $fileName, string $svg, int $ttl): void
    {
        if ($ttl <= 0) {
            return;
        }
        $dir = $this->chartCacheDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($dir . '/' . $fileName, $svg);
    }

    private function fetchDailySkyFromApi(\DateTimeImmutable $moment): DailySky
    {
        $api = $this->config['astro_api'] ?? [];
        $bodiesList = $api['bodies'] ?? [
            'Sun', 'Moon', 'Mercury', 'Venus', 'Mars',
            'Jupiter', 'Saturn', 'Uranus', 'Neptune', 'Pluto',
        ];
        $path = (string) ($api['ephemeris_path'] ?? '/api/v1/ephemeris/calculate');
        $start = $moment->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');

        $raw = $this->request($path, $this->ephemerisPayload($moment, $bodiesList));
        if (!empty($raw['error'])) {
            $msg = is_string($raw['message'] ?? null) ? $raw['message'] : 'Ephemeris API error';
            throw new RuntimeException($msg . (isset($raw['http_code']) ? ' (HTTP ' . $raw['http_code'] . ')' : ''));
        }

        $data = $raw['data'] ?? $raw;
        if (!isset($data['bodies']) || !is_array($data['bodies'])) {
            throw new RuntimeException('Unexpected ephemeris response: missing bodies.');
        }

        $bodies = [];
        foreach ($bodiesList as $name) {
            $row = $data['bodies'][$name] ?? null;
            if (!is_array($row)) {
                continue;
            }
            $bodies[] = new PlanetPosition(
                id: (string) ($row['id'] ?? strtolower($name)),
                name: (string) ($row['name'] ?? $name),
                sign: (string) ($row['sign'] ?? 'Unknown'),
                signId: (string) ($row['sign_id'] ?? strtolower((string) ($row['sign'] ?? 'unknown'))),
                degree: (float) ($row['degree_in_sign'] ?? $row['pos'] ?? 0),
                retrograde: (bool) ($row['retrograde'] ?? false),
                positionText: isset($row['position_text']) ? (string) $row['position_text'] : null,
                absPos: isset($row['abs_pos']) ? (float) $row['abs_pos'] : null,
            );
        }

        if ($bodies === []) {
            throw new RuntimeException('Ephemeris response had no parseable bodies.');
        }

        $phaseRaw = $data['astrology']['moon_phase'] ?? null;
        $lunarPhase = $this->normalizeLunarPhase(
            is_array($phaseRaw) ? $phaseRaw : null,
            $this->findBodyAbs($bodies, 'Sun'),
            $this->findBodyAbs($bodies, 'Moon'),
        );

        return new DailySky(
            moment: $moment,
            bodies: $bodies,
            lunarPhase: $lunarPhase,
            source: 'api',
            meta: [
                'provider' => 'FreeAstroAPI',
                'endpoint' => $path,
                'timestamp' => $data['timestamp'] ?? $start,
                'notable' => $data['astrology']['notable_conditions'] ?? [],
            ],
        );
    }

    /**
     * Documented FreeAstroAPI ephemeris JSON snapshot payload.
     *
     * @param list<string> $bodiesList
     * @return array<string, mixed>
     */
    private function ephemerisPayload(\DateTimeImmutable $moment, array $bodiesList): array
    {
        return [
            'start' => $moment->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
            'bodies' => $bodiesList,
            'format' => 'json',
            'zodiac_type' => 'tropical',
            'include_moon_void_of_course' => true,
        ];
    }

    /**
     * @param array<string, mixed> $birthData
     * @return array<string, mixed>
     */
    private function normalizeNatalPayload(array $birthData): array
    {
        // Simple shape: date + time + lat/lng
        if (isset($birthData['date'], $birthData['time'], $birthData['latitude'], $birthData['longitude'])) {
            return [
                'date' => (string) $birthData['date'],
                'time' => (string) $birthData['time'],
                'latitude' => (float) $birthData['latitude'],
                'longitude' => (float) $birthData['longitude'],
                'house_system' => $birthData['house_system'] ?? 'P',
            ];
        }

        // Rich shape used by the natal UI stub / future form
        $body = [
            'name' => $birthData['name'] ?? 'Subject',
            'year' => (int) $birthData['year'],
            'month' => (int) $birthData['month'],
            'day' => (int) $birthData['day'],
            'time_known' => (bool) ($birthData['time_known'] ?? true),
            'hour' => (int) ($birthData['hour'] ?? 12),
            'minute' => (int) ($birthData['minute'] ?? 0),
            'city' => (string) ($birthData['city'] ?? ''),
            'tz_str' => $birthData['tz_str'] ?? 'AUTO',
            'house_system' => $birthData['house_system'] ?? 'placidus',
            'zodiac_type' => $birthData['zodiac_type'] ?? 'tropical',
            'response_format' => 'full',
        ];

        if (isset($birthData['lat'], $birthData['lng'])) {
            $body['lat'] = (float) $birthData['lat'];
            $body['lng'] = (float) $birthData['lng'];
        }

        return $body;
    }

    private function mockDailySky(\DateTimeImmutable $moment): DailySky
    {
        $doy = (int) $moment->format('z');
        $hour = (int) $moment->format('G');

        $signs = [
            'Aries', 'Taurus', 'Gemini', 'Cancer', 'Leo', 'Virgo',
            'Libra', 'Scorpio', 'Sagittarius', 'Capricorn', 'Aquarius', 'Pisces',
        ];

        $seedBodies = [
            'Sun' => 0, 'Moon' => 3, 'Mercury' => 1, 'Venus' => 2, 'Mars' => 4,
            'Jupiter' => 5, 'Saturn' => 6, 'Uranus' => 7, 'Neptune' => 8, 'Pluto' => 9,
        ];

        $bodies = [];
        $index = 0;
        foreach ($seedBodies as $name => $offset) {
            $signIndex = ($doy + $offset + intdiv($hour, 6)) % 12;
            $degree = fmod(($doy * (1.2 + $offset * 0.17) + $hour * 0.4 + $index * 7.3), 30.0);
            $retrograde = in_array($name, ['Mercury', 'Jupiter', 'Uranus'], true)
                && (($doy + $offset) % 9 === 0);

            $sign = $signs[$signIndex];
            $bodies[] = new PlanetPosition(
                id: strtolower($name),
                name: $name,
                sign: $sign,
                signId: strtolower($sign),
                degree: round($degree, 3),
                retrograde: $retrograde,
                absPos: round($signIndex * 30 + $degree, 3),
            );
            $index++;
        }

        $sunAbs = $this->findBodyAbs($bodies, 'Sun') ?? 0.0;
        $moonAbs = $this->findBodyAbs($bodies, 'Moon') ?? 0.0;
        $lunarPhase = $this->normalizeLunarPhase(null, $sunAbs, $moonAbs);

        return new DailySky(
            moment: $moment,
            bodies: $bodies,
            lunarPhase: $lunarPhase,
            source: 'mock',
            meta: [
                'provider' => 'local-mock',
                'note' => 'Set FREEASTROAPI_KEY in .env for live FreeAstroAPI ephemeris.',
            ],
        );
    }

    /** @param list<PlanetPosition> $bodies */
    private function findBodyAbs(array $bodies, string $name): ?float
    {
        foreach ($bodies as $body) {
            if (strcasecmp($body->name, $name) === 0) {
                return $body->absPos;
            }
        }
        return null;
    }

    /** @param array<string, mixed>|null $phaseRaw */
    private function normalizeLunarPhase(?array $phaseRaw, ?float $sunAbs, ?float $moonAbs): LunarPhase
    {
        if (is_array($phaseRaw) && isset($phaseRaw['name'])) {
            $name = (string) $phaseRaw['name'];
            $angle = (float) ($phaseRaw['phase_angle_deg'] ?? 0);
            $waxing = (bool) ($phaseRaw['is_waxing'] ?? ($angle < 180));
            return new LunarPhase(
                name: $name,
                phaseAngleDeg: $angle,
                isWaxing: $waxing,
                slug: $this->slugifyPhase($name),
            );
        }

        $angle = 0.0;
        if ($sunAbs !== null && $moonAbs !== null) {
            $angle = fmod(($moonAbs - $sunAbs) + 360.0, 360.0);
        }

        $name = match (true) {
            $angle < 22.5 || $angle >= 337.5 => 'New Moon',
            $angle < 67.5 => 'Waxing Crescent',
            $angle < 112.5 => 'First Quarter',
            $angle < 157.5 => 'Waxing Gibbous',
            $angle < 202.5 => 'Full Moon',
            $angle < 247.5 => 'Waning Gibbous',
            $angle < 292.5 => 'Last Quarter',
            default => 'Waning Crescent',
        };

        return new LunarPhase(
            name: $name,
            phaseAngleDeg: round($angle, 3),
            isWaxing: $angle < 180,
            slug: $this->slugifyPhase($name),
        );
    }

    private function slugifyPhase(string $name): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? 'phase';
        return trim($slug, '-');
    }
}
