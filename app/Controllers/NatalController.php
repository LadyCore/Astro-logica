<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AstroService;
use App\Support\BirthForm;

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
        $natal = null;
        $error = null;
        $astro = new AstroService($this->config);

        if ($submitted) {
            if (!$astro->hasApiKey()) {
                $error = 'Configurá FREE_ASTRO_API_KEY en .env para calcular la carta astral.';
            } else {
                try {
                    $payload = BirthForm::toBirthPayload($form);
                    $natal = $astro->getNatalChart($payload);
                    usleep(1_100_000);
                    $chartSvg = $astro->getBirthChartSvg($payload, 'ivory-ink');
                    if ($chartSvg === null) {
                        $error = 'La API devolvió datos, pero no el SVG de la carta. Probá de nuevo en unos segundos.';
                    }
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }

        $this->view('cartas/carta-astral', [
            'title' => 'Carta Astral',
            'pageClass' => 'page-cartas page-carta-astral',
            'activeNav' => 'cartas',
            'activeCard' => 'carta-astral',
            'form' => $form,
            'submitted' => $submitted,
            'chartSvg' => $chartSvg,
            'natal' => $natal,
            'error' => $error,
            'apiReady' => $astro->hasApiKey(),
        ]);
    }
}
