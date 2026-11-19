<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AstroService;
use App\Support\BirthForm;

final class HoroscopeController extends Controller
{
    public function index(): void
    {
        $defaults = BirthForm::defaults($this->config);
        $submitted = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
            || isset($_GET['sign']);
        $source = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? $_POST : $_GET;
        $form = $submitted
            ? BirthForm::fromRequest($source, $defaults)
            : $defaults;

        $horoscope = null;
        $error = null;
        $astro = new AstroService($this->config);

        if ($submitted) {
            if (!$astro->hasApiKey()) {
                $error = $this->t('api.missing');
            } else {
                $raw = $astro->getDailySignHoroscope((string) $form['sign'], 'today', 'en');
                if (!empty($raw['error'])) {
                    $error = is_string($raw['message'] ?? null)
                        ? (string) $raw['message']
                        : $this->t('api.chart_missing');
                } else {
                    $horoscope = $raw['data'] ?? $raw;
                }
            }
        }

        $this->view('cartas/tu-horoscopo', [
            'title' => $this->t('horoscope.title'),
            'pageClass' => 'page-cartas page-horoscopo',
            'activeNav' => 'horoscopo',
            'activeCard' => 'horoscopo',
            'form' => $form,
            'submitted' => $submitted,
            'horoscope' => $horoscope,
            'error' => $error,
            'apiReady' => $astro->hasApiKey(),
        ]);
    }
}
