<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AstroService;
use App\Support\BirthForm;
use App\Support\PlanetMeanings;

final class NatalController extends Controller
{
    public function index(): void
    {
        $defaults = BirthForm::defaults($this->config);
        $submitted = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
        $form = $submitted
            ? BirthForm::fromRequest($_POST, $defaults)
            : $defaults;

        $chartSvg = null;
        $primaryBodies = [];
        $secondaryBodies = [];
        $error = null;
        $astro = new AstroService($this->config);

        $locale = $this->lang();
        if ($submitted) {
            if (!$astro->hasApiKey()) {
                $error = $this->t('api.missing');
            } else {
                try {
                    $payload = BirthForm::toBirthPayload($form);
                    $natal = $astro->getNatalChart($payload);
                    $planets = $natal->payload['planets'] ?? [];
                    $split = PlanetMeanings::splitPrimarySecondary(
                        PlanetMeanings::positionsFromApi(is_array($planets) ? $planets : [], $locale)
                    );
                    $primaryBodies = $split['primary'];
                    $secondaryBodies = $split['secondary'];
                    usleep(1_100_000);
                    $chartSvg = $astro->getBirthChartSvg($payload, 'ivory-ink');
                    if ($chartSvg === null) {
                        $error = $this->t('api.chart_missing');
                    }
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }

        $this->view('cartas/carta-astral', [
            'title' => $this->t('carta.title'),
            'pageClass' => 'page-cartas page-carta-astral',
            'activeNav' => 'cartas',
            'activeCard' => 'carta-astral',
            'form' => $form,
            'submitted' => $submitted,
            'chartSvg' => $chartSvg,
            'primaryBodies' => $primaryBodies,
            'secondaryBodies' => $secondaryBodies,
            'error' => $error,
            'apiReady' => $astro->hasApiKey(),
        ]);
    }
}
