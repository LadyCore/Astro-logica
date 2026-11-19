<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AstroService;

final class HoyController extends Controller
{
    /**
     * Cielo del día: datos desde AstroService (API o mock) → vista hoy/index.
     */
    public function index(): void
    {
        $astro = new AstroService($this->config);
        $sky = $astro->getDailySky();

        // Chart SVG is a second API call (free tier ≈ 1 rps); brief pause if live.
        $chartSvg = null;
        if ($astro->hasApiKey()) {
            if ($sky->source === 'api') {
                usleep(1_100_000);
            }
            $chartSvg = $astro->getDailySkyChartSvg($sky->moment, 'ivory-ink');
        }

        $formatter = new \IntlDateFormatter(
            'es_AR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            $this->config['timezone'],
            \IntlDateFormatter::GREGORIAN,
            "'Hoy,' d 'de' MMMM 'de' yyyy"
        );
        $momentLabel = $formatter->format($sky->moment) ?: $sky->moment->format('Y-m-d');

        // Badge "Datos demo · sin API key" solo cuando source === mock (sin key).
        $usingLiveApi = $sky->source === 'api';
        $showDemoNoKeyBadge = $sky->source === 'mock';
        $skySourceLabel = match ($sky->source) {
            'api' => 'FreeAstroAPI · en vivo',
            'mock-fallback' => 'Datos demo · respaldo local',
            default => 'Datos demo · sin API key',
        };

        $this->view('hoy/index', [
            'title' => 'Cielo del día',
            'sky' => $sky,
            'chartSvg' => $chartSvg,
            'momentLabel' => \mb_strtoupper($momentLabel, 'UTF-8'),
            'usingLiveApi' => $usingLiveApi,
            'showDemoNoKeyBadge' => $showDemoNoKeyBadge,
            'skySourceLabel' => $skySourceLabel,
            'pageClass' => 'page-hoy',
            'activeNav' => 'hoy',
        ]);
    }
}
