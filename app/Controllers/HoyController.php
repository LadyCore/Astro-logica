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

        $formatter = new \IntlDateFormatter(
            'es_AR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            $this->config['timezone'],
            \IntlDateFormatter::GREGORIAN,
            "'Hoy,' d 'de' MMMM 'de' yyyy"
        );
        $momentLabel = $formatter->format($sky->moment) ?: $sky->moment->format('Y-m-d');

        $usingLiveApi = $sky->source === 'api';
        $skySourceLabel = match ($sky->source) {
            'api' => 'FreeAstroAPI · en vivo',
            'mock-fallback' => 'Datos demo · respaldo local',
            default => 'Datos demo · sin API key',
        };

        $this->view('hoy/index', [
            'title' => 'Cielo del día',
            'sky' => $sky,
            'momentLabel' => \mb_strtoupper($momentLabel, 'UTF-8'),
            'usingLiveApi' => $usingLiveApi,
            'skySourceLabel' => $skySourceLabel,
            'pageClass' => 'page-hoy',
            'activeNav' => 'hoy',
        ]);
    }
}
