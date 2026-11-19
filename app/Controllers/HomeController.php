<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AstroService;

final class HomeController extends Controller
{
    public function index(): void
    {
        $astro = new AstroService($this->config);
        $sky = $astro->getDailySky();

        $formatter = new \IntlDateFormatter(
            'es_AR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            $this->config['timezone'],
            \IntlDateFormatter::GREGORIAN,
            "'Hoy,' d 'de' MMMM 'de' yyyy"
        );
        $momentLabel = $formatter->format($sky->moment) ?: $sky->moment->format('Y-m-d');

        $this->view('home/index', [
            'title' => 'Inicio',
            'sky' => $sky,
            'momentLabel' => \mb_strtoupper($momentLabel, 'UTF-8'),
            'usingLiveApi' => $sky->source === 'api',
            'season' => $this->zodiacSeason($sky->moment),
            'pageClass' => 'page-home',
            'activeNav' => 'inicio',
        ]);
    }

    /**
     * Approximate tropical zodiac seasons (Western astrology dates).
     *
     * @return array{slug:string,name:string,range:string,blurb:string,glyph:string}
     */
    private function zodiacSeason(\DateTimeImmutable $moment): array
    {
        $m = (int) $moment->format('n');
        $d = (int) $moment->format('j');
        $md = $m * 100 + $d;

        $seasons = [
            ['slug' => 'capricorn', 'name' => 'Capricornio', 'glyph' => '♑', 'start' => 1222, 'end' => 119, 'range' => 'DEL 22 DE DICIEMBRE AL 19 DE ENERO', 'blurb' => 'Tiempo de estructura y ambición serena: consolidá lo esencial con paciencia y propósito.'],
            ['slug' => 'aquarius', 'name' => 'Acuario', 'glyph' => '♒', 'start' => 120, 'end' => 218, 'range' => 'DEL 20 DE ENERO AL 18 DE FEBRERO', 'blurb' => 'Visión colectiva e independencia lúcida: innová sin perder el corazón.'],
            ['slug' => 'pisces', 'name' => 'Piscis', 'glyph' => '♓', 'start' => 219, 'end' => 320, 'range' => 'DEL 19 DE FEBRERO AL 20 DE MARZO', 'blurb' => 'Sueño, empatía y disolución creativa: escuchá lo sutil antes de actuar.'],
            ['slug' => 'aries', 'name' => 'Aries', 'glyph' => '♈', 'start' => 321, 'end' => 419, 'range' => 'DEL 21 DE MARZO AL 19 DE ABRIL', 'blurb' => 'Impulso vital y coraje limpio: empezá con fuego consciente.'],
            ['slug' => 'taurus', 'name' => 'Tauro', 'glyph' => '♉', 'start' => 420, 'end' => 520, 'range' => 'DEL 20 DE ABRIL AL 20 DE MAYO', 'blurb' => 'Placeres simples y constancia sensual: construí con cuerpo y tierra.'],
            ['slug' => 'gemini', 'name' => 'Géminis', 'glyph' => '♊', 'start' => 521, 'end' => 620, 'range' => 'DEL 21 DE MAYO AL 20 DE JUNIO', 'blurb' => 'Curiosidad, palabras y conexiones ágiles: aprendé en movimiento.'],
            ['slug' => 'cancer', 'name' => 'Cáncer', 'glyph' => '♋', 'start' => 621, 'end' => 722, 'range' => 'DEL 21 DE JUNIO AL 22 DE JULIO', 'blurb' => 'Cuidado, memoria afectiva y nido interior: protegé lo que amás.'],
            ['slug' => 'leo', 'name' => 'Leo', 'glyph' => '♌', 'start' => 723, 'end' => 822, 'range' => 'DEL 23 DE JULIO AL 22 DE AGOSTO', 'blurb' => 'Brillo creativo y generosidad del corazón: dejá que te vean.'],
            ['slug' => 'virgo', 'name' => 'Virgo', 'glyph' => '♍', 'start' => 823, 'end' => 922, 'range' => 'DEL 23 DE AGOSTO AL 22 DE SEPTIEMBRE', 'blurb' => 'Orden amoroso y atención a lo pequeño: refiná con cuidado.'],
            ['slug' => 'libra', 'name' => 'Libra', 'glyph' => '♎', 'start' => 923, 'end' => 1022, 'range' => 'DEL 23 DE SEPTIEMBRE AL 22 DE OCTUBRE', 'blurb' => 'Belleza, diálogo y equilibrios delicados: armonizá sin perderte.'],
            ['slug' => 'scorpio', 'name' => 'Escorpio', 'glyph' => '♏', 'start' => 1023, 'end' => 1121, 'range' => 'DEL 23 DE OCTUBRE AL 21 DE NOVIEMBRE', 'blurb' => 'Intensidad, verdad profunda y transformación: mirá lo que late debajo.'],
            ['slug' => 'sagittarius', 'name' => 'Sagitario', 'glyph' => '♐', 'start' => 1122, 'end' => 1221, 'range' => 'DEL 22 DE NOVIEMBRE AL 21 DE DICIEMBRE', 'blurb' => 'Fe expansiva y sed de horizonte: crecé con sentido y libertad.'],
        ];

        foreach ($seasons as $season) {
            $start = $season['start'];
            $end = $season['end'];
            $inRange = $start <= $end
                ? ($md >= $start && $md <= $end)
                : ($md >= $start || $md <= $end);
            if ($inRange) {
                return $season;
            }
        }

        return $seasons[10]; // scorpio fallback
    }
}
