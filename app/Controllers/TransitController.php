<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AstroService;
use App\Support\BirthForm;

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
        $error = null;
        $astro = new AstroService($this->config);

        if ($submitted) {
            if (!$astro->hasApiKey()) {
                $error = 'Configurá FREE_ASTRO_API_KEY en .env para calcular tránsitos.';
            } else {
                try {
                    $payload = BirthForm::toBirthPayload($form);
                    $when = (string) ($form['transit_date'] ?? '');
                    $chartSvg = $astro->getTransitChartSvg($payload, $when !== '' ? $when : null, 'ivory-ink');
                    if ($chartSvg === null) {
                        $error = 'No se pudo generar el SVG de tránsitos. Probá de nuevo en unos segundos.';
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
            'error' => $error,
            'apiReady' => $astro->hasApiKey(),
        ]);
    }
}
