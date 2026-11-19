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
 * Page order:
 *   1. Portada — datos del consultante (dibujados encima del diseño Canva)
 *   2. Gráfico de la carta natal (SVG)
 *   3+. Interpretación (posiciones + textos cortos)
 *
 * Template: storage/templates/reporte-cn.pdf — tune coords in config/natal_report.php.
 */
final class NatalReportPdfService
{
    /** @var array<string, mixed> */
    private array $layout;

    private string $templatePath;

    /** @var list<string> */
    private array $tempFiles = [];

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
     * @param string|null $chartSvg Natal wheel SVG markup (Ivory Ink), optional.
     * @return string Raw PDF binary
     */
    public function generate(array $birth, array $bodies, string $locale = 'es', ?string $chartSvg = null): string
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

        try {
            // 1) Datos del usuario — portada Canva, texto encima de todo.
            $this->addTemplatePage($pdf, 1);
            $this->stampCover($pdf, $birth, $locale);

            // 2) Gráfico de la carta natal.
            $this->stampChart($pdf, $chartSvg, $locale, $pageCount);

            // 3) Interpretación — posiciones planetarias.
            $this->stampPlanets($pdf, $bodies, $locale, $pageCount);

            return $pdf->Output('', 'S');
        } finally {
            $this->cleanupTempFiles();
        }
    }

    /**
     * Stream PDF as a download response.
     *
     * @param array<string, mixed> $birth
     * @param list<PlanetPosition> $bodies
     */
    public function download(array $birth, array $bodies, string $locale = 'es', ?string $chartSvg = null): void
    {
        $binary = $this->generate($birth, $bodies, $locale, $chartSvg);
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

        // Drawn after useTemplate → sits on top of Canva artwork.
        $this->writeField($pdf, $cover['name'] ?? [], $name);
        $this->writeField($pdf, $cover['birth_date'] ?? [], $date);
        $this->writeField($pdf, $cover['place'] ?? [], $place);
    }

    private function stampChart(Fpdi $pdf, ?string $chartSvg, string $locale, int $templatePages): void
    {
        $cfg = is_array($this->layout['chart'] ?? null) ? $this->layout['chart'] : [];
        $frames = $this->contentFrames($templatePages);
        $this->addTemplatePage($pdf, $frames[0]);

        $headings = is_array($cfg['heading'] ?? null) ? $cfg['heading'] : [];
        $headingText = (string) ($headings[$locale] ?? ($locale === 'en' ? 'Your Natal Chart' : 'Tu Carta Natal'));
        $x = (float) ($cfg['svg_x'] ?? 28);
        $w = (float) ($cfg['svg_w'] ?? 230);
        $headingY = (float) ($cfg['heading_y'] ?? 28);
        $headingSize = (float) ($cfg['heading_size'] ?? 20);
        $headingColor = $this->rgb($cfg['heading_color'] ?? [28, 24, 68]);
        $this->stampSectionHeading($pdf, $headingText, $x, $w, $headingY, $headingSize, $headingColor);

        $svg = is_string($chartSvg) ? trim($chartSvg) : '';
        $y = (float) ($cfg['svg_y'] ?? 52);
        $h = (float) ($cfg['svg_h'] ?? 230);

        if ($svg === '' || !str_contains($svg, '<svg')) {
            $this->stampChartMissing($pdf, $cfg, $locale, $x, $y, $w);
            return;
        }

        // Prefer PNG rasterization (cleaner than TCPDF's SVG parser on FreeAstroAPI wheels).
        $png = $this->rasterizeSvgToPng($svg);
        if ($png !== null) {
            $pdf->Image($png, $x, $y, $w, $h, 'PNG', '', '', false, 300, '', false, false, 0, false, false, true);
            return;
        }

        if (function_exists('xml_parser_create')) {
            $path = $this->writeTempSvg($svg);
            $pdf->ImageSVG($path, $x, $y, $w, $h, '', '', '', 0, true);
            return;
        }

        $this->stampChartMissing($pdf, $cfg, $locale, $x, $y, $w);
    }

    /**
     * @param array<string, mixed> $cfg
     */
    private function stampChartMissing(Fpdi $pdf, array $cfg, string $locale, float $x, float $y, float $w): void
    {
        $missing = is_array($cfg['missing'] ?? null) ? $cfg['missing'] : [];
        $msg = (string) ($missing[$locale] ?? ($locale === 'en'
            ? 'The natal chart graphic could not be included in this report.'
            : 'No se pudo incluir el gráfico de la carta en este reporte.'));
        $pdf->SetTextColor(90, 78, 64);
        $pdf->SetFont('dejavusans', '', 12);
        $pdf->SetXY($x, $y + 40);
        $pdf->MultiCell($w, 6, $msg, 0, 'C');
    }

    /**
     * Rasterize SVG → PNG via PyMuPDF when available (best fidelity for natal wheels).
     */
    private function rasterizeSvgToPng(string $svg): ?string
    {
        $svgPath = $this->writeTempSvg($svg);
        $pngPath = preg_replace('/\.svg$/i', '.png', $svgPath) ?: ($svgPath . '.png');
        $script = <<<'PY'
import sys
try:
    import pymupdf
except ImportError:
    sys.exit(2)
doc = pymupdf.open(sys.argv[1])
page = doc[0]
pix = page.get_pixmap(matrix=pymupdf.Matrix(2, 2), alpha=False)
pix.save(sys.argv[2])
PY;
        $cmd = 'python3 -c ' . escapeshellarg($script) . ' ' . escapeshellarg($svgPath) . ' ' . escapeshellarg($pngPath) . ' 2>/dev/null';
        exec($cmd, $out, $code);
        if ($code !== 0 || !is_readable($pngPath)) {
            return null;
        }
        $this->tempFiles[] = $pngPath;
        return $pngPath;
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
        $startY = (float) ($cfg['start_y'] ?? 54);
        $lineH = (float) ($cfg['line_height'] ?? 28);
        $x = (float) ($cfg['left_x'] ?? 36);
        $w = (float) ($cfg['width'] ?? 214);
        $max = max(1, (int) ($cfg['max_lines_per_page'] ?? 9));
        $titleSize = (float) ($cfg['title_size'] ?? 13);
        $bodySize = (float) ($cfg['body_size'] ?? 12);
        $noteSize = (float) ($cfg['note_size'] ?? 11);
        $titleColor = $this->rgb($cfg['title_color'] ?? [28, 24, 68]);
        $metaColor = $this->rgb($cfg['meta_color'] ?? [130, 72, 48]);
        $noteColor = $this->rgb($cfg['note_color'] ?? [48, 44, 62]);
        $headingY = (float) ($cfg['heading_y'] ?? 26);
        $headingSize = (float) ($cfg['heading_size'] ?? 20);
        $subY = (float) ($cfg['subheading_y'] ?? 40);
        $subSize = (float) ($cfg['subheading_size'] ?? 13);
        $headingColor = $this->rgb($cfg['heading_color'] ?? [28, 24, 68]);
        $headings = is_array($cfg['heading'] ?? null) ? $cfg['heading'] : [];
        $subheadings = is_array($cfg['subheading'] ?? null) ? $cfg['subheading'] : [];
        $headingText = (string) ($headings[$locale] ?? ($locale === 'en' ? 'Interpretation' : 'Interpretación'));
        $subText = (string) ($subheadings[$locale] ?? ($locale === 'en' ? 'Planetary positions' : 'Posiciones planetarias'));

        $frames = $this->contentFrames($templatePages);
        $frameIdx = 0;
        $this->addTemplatePage($pdf, $frames[$frameIdx]);
        $this->stampSectionHeading($pdf, $headingText, $x, $w, $headingY, $headingSize, $headingColor);
        $this->stampSectionHeading($pdf, $subText, $x, $w, $subY, $subSize, $metaColor);

        $y = $startY;
        $linesOnPage = 0;

        foreach ($bodies as $body) {
            if ($linesOnPage >= $max) {
                $frameIdx = ($frameIdx + 1) % count($frames);
                $this->addTemplatePage($pdf, $frames[$frameIdx]);
                $this->stampSectionHeading($pdf, $headingText, $x, $w, $headingY, $headingSize, $headingColor);
                $this->stampSectionHeading($pdf, $subText, $x, $w, $subY, $subSize, $metaColor);
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

            $pdf->SetTextColor(...$titleColor);
            $pdf->SetFont('dejavusans', 'B', $titleSize);
            $pdf->SetXY($x, $y);
            $pdf->Cell($w, 5.5, sprintf('%s  %s', $glyph, $name), 0, 0, 'L', false, '', 0, false, 'T', 'M');

            $pdf->SetTextColor(...$metaColor);
            $pdf->SetFont('dejavusans', '', $bodySize);
            $pdf->SetXY($x, $y + 6.2);
            $pdf->Cell(
                $w,
                5,
                trim(sprintf('%s %s  %s%s', $signGlyph, $sign, $degree, $rx)),
                0,
                0,
                'L'
            );

            $pdf->SetTextColor(...$noteColor);
            $pdf->SetFont('dejavusans', '', $noteSize);
            $pdf->SetXY($x, $y + 12.2);
            $pdf->MultiCell($w, 4.4, $note, 0, 'L', false, 1);

            $y += $lineH;
            $linesOnPage++;
        }
    }

    /**
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
        $pdf->Cell($w, $size * 0.5, $text, 0, 0, $align, false, '', 0, false, 'T', 'M');
    }

    private function writeTempSvg(string $svg): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $path = $dir . '/natal-pdf-' . bin2hex(random_bytes(8)) . '.svg';
        // Ensure XML declaration for TCPDF's SVG parser.
        if (!str_starts_with($svg, '<?xml')) {
            $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $svg;
        }
        if (file_put_contents($path, $svg) === false) {
            throw new RuntimeException('No se pudo preparar el SVG de la carta para el PDF.');
        }
        $this->tempFiles[] = $path;
        return $path;
    }

    private function cleanupTempFiles(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->tempFiles = [];
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
