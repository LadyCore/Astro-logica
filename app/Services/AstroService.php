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
     * Raw ephemeris call matching the FreeAstroAPI calculate contract
     * (date + location + house system). Returns decoded JSON or an error array.
     *
     * @return array<string, mixed>
     */
    public function getDailySkyRaw(?string $date = null): array
    {
        $date ??= (new \DateTimeImmutable('now', new \DateTimeZone(
            (string) ($this->config['timezone'] ?? 'UTC')
        )))->format('Y-m-d');

        $payload = [
            'date' => $date,
            'latitude' => 0,
            'longitude' => 0,
            'house_system' => 'P',
        ];

        return $this->request('/api/v1/ephemeris/calculate', $payload);
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
                'FREEASTROAPI_KEY is required for natal chart calculation.'
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
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function request(string $endpoint, array $payload): array
    {
        $url = str_starts_with($endpoint, 'http')
            ? $endpoint
            : $this->baseUrl . $endpoint;

        $ch = curl_init($url);
        if ($ch === false) {
            return [
                'error' => true,
                'message' => 'No se pudo iniciar cURL',
                'http_code' => 0,
            ];
        }

        $timeout = (int) ($this->config['astro_api']['timeout'] ?? 15);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                // FreeAstroAPI accepts x-api-key; Bearer kept for the documented client shape.
                'Authorization: Bearer ' . $this->apiKey,
                'x-api-key: ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
            CURLOPT_TIMEOUT => $timeout,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false || $response === '') {
            return [
                'error' => true,
                'message' => $curlError !== '' ? $curlError : 'Error al consultar la API',
                'http_code' => $httpCode,
            ];
        }

        $data = json_decode($response, true);

        return is_array($data)
            ? $data
            : ['error' => true, 'message' => 'Respuesta inválida', 'http_code' => $httpCode];
    }

    private function fetchDailySkyFromApi(\DateTimeImmutable $moment): DailySky
    {
        $api = $this->config['astro_api'] ?? [];
        $start = $moment->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
        $bodiesList = $api['bodies'] ?? [
            'Sun', 'Moon', 'Mercury', 'Venus', 'Mars',
            'Jupiter', 'Saturn', 'Uranus', 'Neptune', 'Pluto',
        ];
        $path = (string) ($api['ephemeris_path'] ?? '/api/v1/ephemeris/calculate');

        $payload = [
            'start' => $start,
            'bodies' => $bodiesList,
            'format' => 'json',
            'zodiac_type' => 'tropical',
            'include_retrograde' => true,
            'include_sign_degree' => true,
            'include_moon_void_of_course' => true,
            // Also send the simpler shape from the documented client.
            'date' => $moment->format('Y-m-d'),
            'latitude' => 0,
            'longitude' => 0,
            'house_system' => 'P',
        ];

        $raw = $this->request($path, $payload);
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
