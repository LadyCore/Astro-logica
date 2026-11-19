<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlanetPosition;
use App\Support\PlanetMeanings;
use RuntimeException;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Builds a natal-chart PDF by stamping dynamic data onto a Canva PDF template (FPDI + TCPDF).
 *
 * Template path (default): storage/templates/reporte-cn.pdf
 * Drop your Canva export "REPORTE CN .pdf" there (renamed) and adjust coordinates in config/natal_report.php.
 *
 * Expected Canva structure (REPORTE CN):
 *   1 — cover (name / birth / place stamped under the author block)
 *   2 — intro (“Tu Carta Natal”) kept as-is
 *   3–4 — blank ornamental frames for planet rows (cycled if needed)
 */
final class NatalReportPdfService
{
    /** @var array<string, mixed> */
    private array $layout;

    private string $templatePath;

    /**
     * @param array<string, mixed> $config Full app config (or at least natal_report + timezone).
     */
    public function __construct(private readonly array $config)
    {
        $layout = $config['natal_report'] ?? null;
        if (!is_array($layout)) {
            $layoutFile = dirname(__DIR__, 2) . '/config/natal_report.php';
            $layout = is_readable($layoutFile) ? require $layoutFile : [];
        }
        $this->layout = is_array($layout) ? $layout : [];
        $this->templatePath = (string) ($this->layout['template']
            ?? dirname(__DIR__, 2) . '/storage/templates/reporte-cn.pdf');
    }

    public function templatePath(): string
    {
        return $this->templatePath;
    }

    public function templateExists(): bool
    {
        return is_readable($this->templatePath);
    }

    /**
     * @param array<string, mixed> $birth Normalized birth payload (name, year, month, day, …).
     * @param list<PlanetPosition> $bodies Ordered planet positions for the report.
     * @return string Raw PDF binary
     */
    public function generate(array $birth, array $bodies, string $locale = 'es'): string
    {
        if (!$this->templateExists()) {
            throw new RuntimeException(
                'Falta la plantilla PDF. Subí tu archivo de Canva a: storage/templates/reporte-cn.pdf'
            );
        }

        $locale = $locale === 'en' ? 'en' : 'es';
        $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->SetCreator('Astro-lógica');
        $pdf->SetAuthor('Astro-lógica');
        $name = (string) ($birth['name'] ?? 'Consultante');
        $pdf->SetTitle(($locale === 'en' ? 'Natal Chart — ' : 'Carta Natal — ') . $name);
        $pdf->SetMargins(0, 0, 0);

        $pageCount = $pdf->setSourceFile($this->templatePath);
        if ($pageCount < 1) {
            throw new RuntimeException('La plantilla PDF no tiene páginas legibles.');
        }

        // --- Page 1: cover / identity ---
        $this->addTemplatePage($pdf, 1);
        $this->stampCover($pdf, $birth, $locale);

        // --- Optional passthrough pages (intro copy from Canva) ---
        $passthrough = $this->intList($this->layout['passthrough_pages'] ?? [2]);
        foreach ($passthrough as $pageNo) {
            if ($pageNo >= 1 && $pageNo <= $pageCount && $pageNo !== 1) {
                $this->addTemplatePage($pdf, $pageNo);
            }
        }

        // --- Content frames: planet positions + short interpretations ---
        $this->stampPlanets($pdf, $bodies, $locale, $pageCount);

        return $pdf->Output('', 'S');
    }

    /**
     * Stream PDF as a download response.
     *
     * @param array<string, mixed> $birth
     * @param list<PlanetPosition> $bodies
     */
    public function download(array $birth, array $bodies, string $locale = 'es'): void
    {
        $binary = $this->generate($birth, $bodies, $locale);
        $slug = preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) ($birth['name'] ?? 'carta')) ?: 'carta';
        $filename = 'carta-natal-' . strtolower($slug) . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($binary));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $binary;
    }

    /**
     * @param array<string, mixed> $birth
     */
    private function stampCover(Fpdi $pdf, array $birth, string $locale): void
    {
        $cover = is_array($this->layout['cover'] ?? null) ? $this->layout['cover'] : [];
        $name = (string) ($birth['name'] ?? ($locale === 'en' ? 'Guest' : 'Consultante'));
        $date = $this->formatBirthDate($birth, $locale);
        $place = trim((string) ($birth['city'] ?? ''));
        if ($place === '') {
            $place = $locale === 'en' ? 'Location not set' : 'Lugar no indicado';
        }

        $this->writeField($pdf, $cover['name'] ?? [], $name);
        $this->writeField($pdf, $cover['birth_date'] ?? [], $date);
        $this->writeField($pdf, $cover['place'] ?? [], $place);
    }

    /**
     * @param list<PlanetPosition> $bodies
     */
    private function stampPlanets(Fpdi $pdf, array $bodies, string $locale, int $templatePages): void
    {
        if ($bodies === []) {
            return;
        }

        $cfg = is_array($this->layout['planets'] ?? null) ? $this->layout['planets'] : [];
        $startY = (float) ($cfg['start_y'] ?? 48);
        $lineH = (float) ($cfg['line_height'] ?? 26);
        $x = (float) ($cfg['left_x'] ?? 36);
        $w = (float) ($cfg['width'] ?? 214);
        $max = max(1, (int) ($cfg['max_lines_per_page'] ?? 10));
        $titleSize = (float) ($cfg['title_size'] ?? 11);
        $bodySize = (float) ($cfg['body_size'] ?? 10);
        $noteSize = (float) ($cfg['note_size'] ?? 9);
        $titleColor = $this->rgb($cfg['title_color'] ?? [35, 32, 72]);
        $metaColor = $this->rgb($cfg['meta_color'] ?? [120, 72, 55]);
        $noteColor = $this->rgb($cfg['note_color'] ?? [55, 50, 70]);
        $headingY = (float) ($cfg['heading_y'] ?? 28);
        $headingSize = (float) ($cfg['heading_size'] ?? 16);
        $headingColor = $this->rgb($cfg['heading_color'] ?? [35, 32, 72]);
        $headings = is_array($cfg['heading'] ?? null) ? $cfg['heading'] : [];
        $headingText = (string) ($headings[$locale] ?? ($locale === 'en' ? 'Planetary positions' : 'Posiciones planetarias'));

        $frames = $this->contentFrames($templatePages);
        $frameIdx = 0;
        $this->addTemplatePage($pdf, $frames[$frameIdx]);
        $this->stampSectionHeading($pdf, $headingText, $x, $w, $headingY, $headingSize, $headingColor);

        $y = $startY;
        $linesOnPage = 0;

        foreach ($bodies as $body) {
            if ($linesOnPage >= $max) {
                $frameIdx = ($frameIdx + 1) % count($frames);
                $this->addTemplatePage($pdf, $frames[$frameIdx]);
                $this->stampSectionHeading($pdf, $headingText, $x, $w, $headingY, $headingSize, $headingColor);
                $y = $startY;
                $linesOnPage = 0;
            }

            $id = strtolower($body->id);
            $name = PlanetMeanings::bodyName($id, $locale);
            $sign = PlanetMeanings::signName($body->signId, $locale);
            $glyph = PlanetMeanings::BODY_GLYPH[$id] ?? '·';
            $signGlyph = PlanetMeanings::SIGN_GLYPH[strtolower($body->signId)] ?? '';
            $degree = $body->degreeFormatted();
            $rx = $body->retrograde ? ' Rx' : '';
            $note = PlanetMeanings::forBody($body, $locale);

            // Planet title line
            $pdf->SetTextColor(...$titleColor);
            $pdf->SetFont('dejavusans', 'B', $titleSize);
            $pdf->SetXY($x, $y);
            $pdf->Cell($w, 5, sprintf('%s  %s', $glyph, $name), 0, 0, 'L', false, '', 0, false, 'T', 'M');

            // Sign + degree
            $pdf->SetTextColor(...$metaColor);
            $pdf->SetFont('dejavusans', '', $bodySize);
            $pdf->SetXY($x, $y + 5.5);
            $pdf->Cell(
                $w,
                4.5,
                trim(sprintf('%s %s  %s%s', $signGlyph, $sign, $degree, $rx)),
                0,
                0,
                'L'
            );

            // Short interpretation
            $pdf->SetTextColor(...$noteColor);
            $pdf->SetFont('dejavusans', '', $noteSize);
            $pdf->SetXY($x, $y + 11);
            $pdf->MultiCell($w, 4.0, $note, 0, 'L', false, 1);

            $y += $lineH;
            $linesOnPage++;
        }
    }

    /**
     * Template page numbers used as blank content frames (fallback: last page, then 1).
     *
     * @return list<int>
     */
    private function contentFrames(int $templatePages): array
    {
        $configured = $this->intList($this->layout['content_pages'] ?? []);
        $frames = [];
        foreach ($configured as $pageNo) {
            if ($pageNo >= 1 && $pageNo <= $templatePages) {
                $frames[] = $pageNo;
            }
        }
        if ($frames === []) {
            $frames[] = $templatePages >= 1 ? $templatePages : 1;
        }
        return array_values(array_unique($frames));
    }

    /**
     * @param mixed $value
     * @return list<int>
     */
    private function intList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $item) {
            $out[] = (int) $item;
        }
        return $out;
    }

    /**
     * @param array{0:int,1:int,2:int} $color
     */
    private function stampSectionHeading(
        Fpdi $pdf,
        string $text,
        float $x,
        float $w,
        float $y,
        float $size,
        array $color,
    ): void {
        $pdf->SetTextColor(...$color);
        $pdf->SetFont('dejavusans', 'B', $size);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $size * 0.5, $text, 0, 0, 'L', false, '', 0, false, 'T', 'M');
    }

    private function addTemplatePage(Fpdi $pdf, int $templatePageNumber): void
    {
        $tpl = $pdf->importPage($templatePageNumber);
        $size = $pdf->getTemplateSize($tpl);
        $pdf->AddPage($size['orientation'] ?? 'P', [$size['width'], $size['height']]);
        $pdf->useTemplate($tpl);
    }

    /**
     * @param array<string, mixed> $field
     */
    private function writeField(Fpdi $pdf, array $field, string $text): void
    {
        if ($field === [] || $text === '') {
            return;
        }
        $x = (float) ($field['x'] ?? 20);
        $y = (float) ($field['y'] ?? 20);
        $w = (float) ($field['w'] ?? 170);
        $size = (float) ($field['size'] ?? 12);
        $style = (string) ($field['style'] ?? '');
        $align = strtoupper((string) ($field['align'] ?? 'L'));
        if (!in_array($align, ['L', 'C', 'R'], true)) {
            $align = 'L';
        }
        $color = $this->rgb($field['color'] ?? [40, 40, 40]);

        $pdf->SetTextColor(...$color);
        $pdf->SetFont('dejavusans', $style, $size);
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, $size * 0.45, $text, 0, 0, $align, false, '', 0, false, 'T', 'M');
    }

    /**
     * @param array<string, mixed> $birth
     */
    private function formatBirthDate(array $birth, string $locale): string
    {
        $y = (int) ($birth['year'] ?? 0);
        $m = (int) ($birth['month'] ?? 0);
        $d = (int) ($birth['day'] ?? 0);
        $h = (int) ($birth['hour'] ?? 0);
        $min = (int) ($birth['minute'] ?? 0);
        if ($y < 1 || $m < 1 || $d < 1) {
            return $locale === 'en' ? 'Date unknown' : 'Fecha desconocida';
        }

        try {
            $dt = new \DateTimeImmutable(sprintf('%04d-%02d-%02d %02d:%02d:00', $y, $m, $d, $h, $min));
        } catch (\Throwable) {
            return sprintf('%02d/%02d/%04d', $d, $m, $y);
        }

        if ($locale === 'en') {
            return $dt->format('F j, Y · H:i');
        }

        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];
        $monthName = $months[(int) $dt->format('n')] ?? $dt->format('m');
        return sprintf('%d de %s de %s · %s', (int) $dt->format('j'), $monthName, $dt->format('Y'), $dt->format('H:i'));
    }

    /**
     * @param mixed $rgb
     * @return array{0:int,1:int,2:int}
     */
    private function rgb(mixed $rgb): array
    {
        if (!is_array($rgb) || count($rgb) < 3) {
            return [40, 40, 40];
        }
        return [(int) $rgb[0], (int) $rgb[1], (int) $rgb[2]];
    }
}
