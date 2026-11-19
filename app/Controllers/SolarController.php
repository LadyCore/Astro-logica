<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AstroService;
use App\Support\BirthForm;
use App\Support\PlanetMeanings;

final class SolarController extends Controller
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
                    $year = (int) ($form['solar_year'] ?? date('Y'));
                    $data = $astro->getSolarReturnData($payload, $year);
                    if (!empty($data['error'])) {
                        throw new \RuntimeException(
                            is_string($data['message'] ?? null) ? (string) $data['message'] : $this->t('api.chart_missing')
                        );
                    }
                    $planets = $data['planets'] ?? [];
                    $split = PlanetMeanings::splitPrimarySecondary(
                        PlanetMeanings::positionsFromApi(is_array($planets) ? $planets : [], $locale)
                    );
                    $primaryBodies = $split['primary'];
                    $secondaryBodies = $split['secondary'];
                    usleep(1_100_000);
                    $chartSvg = $astro->getSolarReturnChartSvg($payload, $year, 'ivory-ink');
                    if ($chartSvg === null) {
                        $error = $this->t('api.chart_missing');
                    }
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }

        $this->view('cartas/revolucion-solar', [
            'title' => $this->t('solar.title'),
            'pageClass' => 'page-cartas page-revolucion-solar',
            'activeNav' => 'cartas',
            'activeCard' => 'revolucion-solar',
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
