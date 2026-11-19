<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AstroService;
use App\Support\BirthForm;

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
        $error = null;
        $astro = new AstroService($this->config);

        if ($submitted) {
            if (!$astro->hasApiKey()) {
                $error = 'Configurá FREE_ASTRO_API_KEY en .env para calcular la revolución solar.';
            } else {
                try {
                    $payload = BirthForm::toBirthPayload($form);
                    $year = (int) ($form['solar_year'] ?? date('Y'));
                    $chartSvg = $astro->getSolarReturnChartSvg($payload, $year, 'ivory-ink');
                    if ($chartSvg === null) {
                        $error = 'No se pudo generar el SVG de revolución solar. Probá de nuevo en unos segundos.';
                    }
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }

        $this->view('cartas/revolucion-solar', [
            'title' => 'Revolución Solar',
            'pageClass' => 'page-cartas page-revolucion-solar',
            'activeNav' => 'cartas',
            'activeCard' => 'revolucion-solar',
            'form' => $form,
            'submitted' => $submitted,
            'chartSvg' => $chartSvg,
            'error' => $error,
            'apiReady' => $astro->hasApiKey(),
        ]);
    }
}
