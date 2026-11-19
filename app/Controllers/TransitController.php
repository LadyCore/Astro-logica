<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AstroService;
use App\Support\BirthForm;
use App\Support\PlanetMeanings;

final class TransitController extends Controller
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

        if ($submitted) {
            if (!$astro->hasApiKey()) {
                $error = 'Todavía no está configurada la conexión al cielo en vivo.';
            } else {
                try {
                    $payload = BirthForm::toBirthPayload($form);
                    $when = (string) ($form['transit_date'] ?? '');
                    $data = $astro->getTransitsData($payload, $when !== '' ? $when : null);
                    if (!empty($data['error'])) {
                        throw new \RuntimeException(
                            is_string($data['message'] ?? null) ? (string) $data['message'] : 'No se pudieron calcular los tránsitos.'
                        );
                    }
                    $planets = $data['transit_planets'] ?? [];
                    $split = PlanetMeanings::splitPrimarySecondary(
                        PlanetMeanings::positionsFromApi(is_array($planets) ? $planets : [])
                    );
                    $primaryBodies = $split['primary'];
                    $secondaryBodies = $split['secondary'];
                    usleep(1_100_000);
                    $chartSvg = $astro->getTransitChartSvg($payload, $when !== '' ? $when : null, 'ivory-ink');
                    if ($chartSvg === null) {
                        $error = 'Los datos llegaron, pero la rueda no. Probá de nuevo en unos segundos.';
                    }
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }

        $this->view('cartas/transitos', [
            'title' => 'Tránsitos',
            'pageClass' => 'page-cartas page-transitos',
            'activeNav' => 'cartas',
            'activeCard' => 'transitos',
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
