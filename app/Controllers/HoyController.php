<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AstroService;

final class HoyController extends Controller
{
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

        $this->view('hoy/index', [
            'title' => 'Cielo del día',
            'sky' => $sky,
            'momentLabel' => mb_strtoupper($momentLabel, 'UTF-8'),
            'usingLiveApi' => $sky->source === 'api',
            'pageClass' => 'page-hoy',
            'activeNav' => 'hoy',
        ]);
    }
}
