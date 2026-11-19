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
                $error = 'Todavía no está configurada la conexión al cielo en vivo.';
            } else {
                $raw = $astro->getDailySignHoroscope((string) $form['sign'], 'today', 'en');
                if (!empty($raw['error'])) {
                    $error = is_string($raw['message'] ?? null)
                        ? (string) $raw['message']
                        : 'No se pudo obtener el horóscopo diario.';
                } else {
                    $horoscope = $raw['data'] ?? $raw;
                }
            }
        }

        $this->view('cartas/tu-horoscopo', [
            'title' => 'Tu Horóscopo',
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
