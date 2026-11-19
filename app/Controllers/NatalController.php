<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AstroService;
use App\Services\NatalReportPdfService;
use App\Support\BirthForm;
use App\Support\PlanetMeanings;

final class NatalController extends Controller
{
    /**
     * Carta Astral screen: form + live wheel + interpretations.
     */
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

        $pdfReady = (new NatalReportPdfService($this->config))->templateExists();

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
            'pdfReady' => $pdfReady,
            // Download recalculates natal data; only needs API + template.
            'canDownloadPdf' => $astro->hasApiKey() && $pdfReady,
        ]);
    }

    /**
     * POST /carta-astral/pdf — generate & download the natal report PDF from the Canva template.
     */
    public function pdf(): void
    {
        $defaults = BirthForm::defaults($this->config);
        $form = BirthForm::fromRequest($_POST, $defaults);
        $payload = BirthForm::toBirthPayload($form);
        $locale = $this->lang();
        $astro = new AstroService($this->config);
        $report = new NatalReportPdfService($this->config);

        if (!$astro->hasApiKey()) {
            $this->pdfError($this->t('api.missing'), $form);
            return;
        }
        if (!$report->templateExists()) {
            $this->pdfError($this->t('carta.pdf_missing'), $form);
            return;
        }

        try {
            $natal = $astro->getNatalChart($payload);
            $planets = $natal->payload['planets'] ?? [];
            $bodies = PlanetMeanings::positionsFromApi(is_array($planets) ? $planets : [], $locale);
            $split = PlanetMeanings::splitPrimarySecondary($bodies);
            $ordered = array_merge($split['primary'], $split['secondary']);
            if ($ordered === []) {
                throw new \RuntimeException($this->t('api.chart_missing'));
            }
            $report->download($payload, $ordered, $locale);
            exit;
        } catch (\Throwable $e) {
            $this->pdfError($e->getMessage(), $form);
        }
    }

    /**
     * Re-render the Carta Astral page with an error when PDF generation fails.
     *
     * @param array<string, mixed> $form
     */
    private function pdfError(string $message, array $form): void
    {
        $this->view('cartas/carta-astral', [
            'title' => $this->t('carta.title'),
            'pageClass' => 'page-cartas page-carta-astral',
            'activeNav' => 'cartas',
            'activeCard' => 'carta-astral',
            'form' => $form,
            'submitted' => true,
            'chartSvg' => null,
            'primaryBodies' => [],
            'secondaryBodies' => [],
            'error' => $message,
            'apiReady' => (new AstroService($this->config))->hasApiKey(),
            'pdfReady' => (new NatalReportPdfService($this->config))->templateExists(),
            'canDownloadPdf' => false,
        ], 422);
    }
}
