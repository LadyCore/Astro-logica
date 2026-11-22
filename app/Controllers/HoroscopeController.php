<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AstroService;
use App\Support\BirthForm;
use App\Support\HoroscopeLocalizer;
use App\Support\LiveSkyAligner;

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
        $sky = null;
        $astro = new AstroService($this->config);

        if ($submitted) {
            if (!$astro->hasApiKey()) {
                $error = $this->t('api.missing');
            } else {
                // Live sky first — same source / timezone as Cielo del día.
                $sky = $astro->getDailySky();

                // Soft pause so we don't race the ephemeris call on free-tier 1 rps.
                if ($sky->source === 'api') {
                    usleep(1_100_000);
                }

                // Provider daily-sign languages: en/fr/it/hi (no Spanish). Fetch EN, then localize.
                $raw = $astro->getDailySignHoroscope((string) $form['sign'], 'today', 'en');
                if (!empty($raw['error'])) {
                    $error = is_string($raw['message'] ?? null)
                        ? (string) $raw['message']
                        : $this->t('api.chart_missing');
                } else {
                    $data = $raw['data'] ?? $raw;
                    if (is_array($data)) {
                        $localized = (new HoroscopeLocalizer())->localize($data, $this->lang());
                        // Override canned Moon-in-sign / phase claims with live sky.
                        $horoscope = (new LiveSkyAligner())->align($localized, $sky, $this->lang());
                    }
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
            'sky' => $sky,
            'error' => $error,
            'apiReady' => $astro->hasApiKey(),
        ]);
    }
}
