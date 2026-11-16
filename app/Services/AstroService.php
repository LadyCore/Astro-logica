<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailySky;
use App\Models\LunarPhase;
use App\Models\NatalChart;
use App\Models\PlanetPosition;
use RuntimeException;

/**
 * Owns all FreeAstroAPI calls so the astrology engine can be swapped later.
 */
final class AstroService
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly array $config,
    ) {
    }

    public function hasApiKey(): bool
    {
        $key = $this->config['astro_api']['api_key'] ?? null;
        return is_string($key) && $key !== '';
    }

    public function getDailySky(?\DateTimeImmutable $moment = null): DailySky
    {
        $tz = new \DateTimeZone($this->config['timezone']);
        $moment ??= new \DateTimeImmutable('now', $tz);

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
     * @param array{
     *   name?: string,
     *   year: int,
     *   month: int,
     *   day: int,
     *   hour?: int,
     *   minute?: int,
     *   time_known?: bool,
     *   city: string,
     *   lat?: float,
     *   lng?: float,
     *   tz_str?: string,
     *   house_system?: string,
     *   zodiac_type?: string
     * } $input
     */
    public function getNatalChart(array $input): NatalChart
    {
        if (!$this->hasApiKey()) {
            throw new RuntimeException(
                'FREEASTROAPI_KEY is required for natal chart calculation.'
            );
        }

        $body = [
            'name' => $input['name'] ?? 'Subject',
            'year' => (int) $input['year'],
            'month' => (int) $input['month'],
            'day' => (int) $input['day'],
            'time_known' => (bool) ($input['time_known'] ?? true),
            'hour' => (int) ($input['hour'] ?? 12),
            'minute' => (int) ($input['minute'] ?? 0),
            'city' => (string) $input['city'],
            'tz_str' => $input['tz_str'] ?? 'AUTO',
            'house_system' => $input['house_system'] ?? 'placidus',
            'zodiac_type' => $input['zodiac_type'] ?? 'tropical',
            'response_format' => 'full',
        ];

        if (isset($input['lat'], $input['lng'])) {
            $body['lat'] = (float) $input['lat'];
            $body['lng'] = (float) $input['lng'];
        }

        $raw = $this->request('POST', $this->config['astro_api']['natal_path'], $body);

        return new NatalChart(payload: $raw, source: 'api');
    }

    private function fetchDailySkyFromApi(\DateTimeImmutable $moment): DailySky
    {
        $api = $this->config['astro_api'];
        $start = $moment->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');

        $payload = [
            'start' => $start,
            'bodies' => $api['bodies'],
            'format' => 'json',
            'zodiac_type' => 'tropical',
            'include_retrograde' => true,
            'include_sign_degree' => true,
            'include_moon_void_of_course' => true,
        ];

        $raw = $this->request('POST', $api['ephemeris_path'], $payload);
        $data = $raw['data'] ?? $raw;

        if (!isset($data['bodies']) || !is_array($data['bodies'])) {
            throw new RuntimeException('Unexpected ephemeris response: missing bodies.');
        }

        $bodies = [];
        foreach ($api['bodies'] as $name) {
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
                'endpoint' => $api['ephemeris_path'],
                'timestamp' => $data['timestamp'] ?? $start,
                'notable' => $data['astrology']['notable_conditions'] ?? [],
            ],
        );
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $body = []): array
    {
        $api = $this->config['astro_api'];
        $url = $api['base_url'] . $path;
        $key = (string) $api['api_key'];

        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Unable to init cURL.');
        }

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'x-api-key: ' . $key,
        ];

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => (int) ($api['timeout'] ?? 12),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
        ];

        if ($method !== 'GET') {
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_THROW_ON_ERROR);
        }

        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('FreeAstroAPI request failed: ' . $error);
        }

        /** @var array<string, mixed>|null $decoded */
        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('FreeAstroAPI returned non-JSON (HTTP ' . $status . ').');
        }

        if ($status < 200 || $status >= 300) {
            $msg = $decoded['message'] ?? $decoded['error'] ?? ('HTTP ' . $status);
            throw new RuntimeException('FreeAstroAPI error: ' . (is_string($msg) ? $msg : json_encode($msg)));
        }

        return $decoded;
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
