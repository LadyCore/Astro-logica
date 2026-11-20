<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlanetPosition;
use App\Support\PlanetMeanings;
use RuntimeException;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Natal PDF from the Canva template (FPDI + TCPDF).
 *
 * HARD RULE: Pages 1–2 are pure Canva imports — no text/image overlays, no slogan footer.
 *
 * Content order:
 *   3. Chart + consultante data (nombre, fecha, hora, lugar) + slogan footer
 *   4. All interpretations + Instagram/email + slogan footer
 *
 * Coords: config/natal_report.php
 */
final class NatalReportPdfService
{
    /** @var array<string, mixed> */
    private array $layout;

    private string $templatePath;

    /** @var list<string> */
    private array $tempFiles = [];

    /**
     * @param array<string, mixed> $config
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
     * @param array<string, mixed> $birth
     * @param list<PlanetPosition> $bodies
     */
    public function generate(array $birth, array $bodies, string $locale = 'es', ?string $chartSvg = null): string
    {
        if (!$this->templateExists()) {
            throw new RuntimeException(
                'Falta la plantilla PDF. Subí tu archivo de Canva a: storage/templates/reporte-cn.pdf'
            );
        }

        $locale = $locale === 'en' ? 'en' : 'es';
        $pdf = $this->newPdf();
        $name = (string) ($birth['name'] ?? 'Consultante');
        $pdf->SetTitle(($locale === 'en' ? 'Natal Chart — ' : 'Carta Natal — ') . $name);
        $pdf->SetCreator('Astro-lógica');
        $pdf->SetAuthor('Astro-lógica');
        $pdf->SetMargins(0, 0, 0);

        $pageCount = $pdf->setSourceFile($this->templatePath);
        if ($pageCount < 1) {
            throw new RuntimeException('La plantilla PDF no tiene páginas legibles.');
        }

        try {
            // Pages 1–2: Canva ONLY — never stamp overlays/footer here.
            $this->addTemplatePage($pdf, 1);
            if ($pageCount >= 2) {
                $this->addTemplatePage($pdf, 2);
            }

            // Page 3+: dynamic content on blank frames.
            $this->stampChartAndIdentity($pdf, $birth, $chartSvg, $locale, $pageCount);
            $this->stampPlanetsAndContact($pdf, $bodies, $locale, $pageCount);

            return $pdf->Output('', 'S');
        } finally {
            $this->cleanupTempFiles();
        }
    }

    /**
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

    private function newPdf(): Fpdi
    {
        $pdf = new class ('P', 'mm', 'A4', true, 'UTF-8', false) extends Fpdi {
            public function Header(): void
            {
            }

            public function Footer(): void
            {
            }
        };
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false, 0);

        return $pdf;
    }

    /**
     * Page 3: chart on top + nombre / fecha / hora / lugar + slogan footer.
     *
     * @param array<string, mixed> $birth
     */
    private function stampChartAndIdentity(
        Fpdi $pdf,
        array $birth,
        ?string $chartSvg,
        string $locale,
        int $templatePages,
    ): void {
        $chartCfg = is_array($this->layout['chart'] ?? null) ? $this->layout['chart'] : [];
        $identity = is_array($this->layout['identity'] ?? null)
            ? $this->layout['identity']
            : (is_array($this->layout['cover'] ?? null) ? $this->layout['cover'] : []);
        $frames = $this->contentFrames($templatePages);
        $this->addTemplatePage($pdf, $frames[0]);

        $x = (float) ($chartCfg['svg_x'] ?? 48);
        $w = (float) ($chartCfg['svg_w'] ?? 190);
        $headings = is_array($chartCfg['heading'] ?? null) ? $chartCfg['heading'] : [];
        $headingText = (string) ($headings[$locale] ?? ($locale === 'en' ? 'Your Natal Chart' : 'Tu Carta Natal'));
        $this->stampSectionHeading(
            $pdf,
            $headingText,
            $x,
            $w,
            (float) ($chartCfg['heading_y'] ?? 22),
            (float) ($chartCfg['heading_size'] ?? 16),
            $this->rgb($chartCfg['heading_color'] ?? [28, 24, 68]),
        );

        $svg = is_string($chartSvg) ? trim($chartSvg) : '';
        $y = (float) ($chartCfg['svg_y'] ?? 38);
        $h = (float) ($chartCfg['svg_h'] ?? 190);

        if ($svg === '' || !str_contains($svg, '<svg')) {
            $this->stampChartMissing($pdf, $chartCfg, $locale, $x, $y, $w);
        } else {
            $png = $this->rasterizeSvgToPng($svg);
            if ($png !== null) {
                $pdf->Image($png, $x, $y, $w, $h, 'PNG', '', '', false, 300, '', false, false, 0, false, false, true);
            } elseif (function_exists('xml_parser_create')) {
                $path = $this->writeTempSvg($svg);
                $pdf->ImageSVG($path, $x, $y, $w, $h, '', '', '', 0, true);
            } else {
                $this->stampChartMissing($pdf, $chartCfg, $locale, $x, $y, $w);
            }
        }

        $name = (string) ($birth['name'] ?? ($locale === 'en' ? 'Guest' : 'Consultante'));
        $date = $this->formatBirthDateOnly($birth, $locale);
        $time = $this->formatBirthTimeOnly($birth, $locale);
        $place = trim((string) ($birth['city'] ?? ''));
        if ($place === '') {
            $place = $locale === 'en' ? 'Location not set' : 'Lugar no indicado';
        }

        $this->writeField($pdf, $identity['name'] ?? [], $name);
        $this->writeField($pdf, $identity['birth_date'] ?? [], $date);
        $this->writeField($pdf, $identity['birth_time'] ?? [], $time);
        $this->writeField($pdf, $identity['place'] ?? [], $place);

        $this->stampSloganFooter($pdf);
    }

    /**
     * Page 4: all planet interpretations + contact + slogan footer (single page).
     *
     * @param list<PlanetPosition> $bodies
     */
    private function stampPlanetsAndContact(Fpdi $pdf, array $bodies, string $locale, int $templatePages): void
    {
        if ($bodies === []) {
            return;
        }

        $cfg = is_array($this->layout['planets'] ?? null) ? $this->layout['planets'] : [];
        $startY = (float) ($cfg['start_y'] ?? 42);
        $bottomY = (float) ($cfg['bottom_y'] ?? 300);
        $x = (float) ($cfg['left_x'] ?? 34);
        $w = (float) ($cfg['width'] ?? 218);
        $titleSize = (float) ($cfg['title_size'] ?? 10);
        $bodySize = (float) ($cfg['body_size'] ?? 9);
        $noteSize = (float) ($cfg['note_size'] ?? 8.5);
        $titleColor = $this->rgb($cfg['title_color'] ?? [28, 24, 68]);
        $metaColor = $this->rgb($cfg['meta_color'] ?? [130, 72, 48]);
        $noteColor = $this->rgb($cfg['note_color'] ?? [48, 44, 62]);
        $headingY = (float) ($cfg['heading_y'] ?? 20);
        $headingSize = (float) ($cfg['heading_size'] ?? 15);
        $subY = (float) ($cfg['subheading_y'] ?? 31);
        $subSize = (float) ($cfg['subheading_size'] ?? 10.5);
        $headingColor = $this->rgb($cfg['heading_color'] ?? [28, 24, 68]);
        $headings = is_array($cfg['heading'] ?? null) ? $cfg['heading'] : [];
        $subheadings = is_array($cfg['subheading'] ?? null) ? $cfg['subheading'] : [];
        $headingText = (string) ($headings[$locale] ?? ($locale === 'en' ? 'Interpretation' : 'Interpretación'));
        $subText = (string) ($subheadings[$locale] ?? ($locale === 'en' ? 'Planetary positions' : 'Posiciones planetarias'));

        $count = count($bodies);
        $available = max(80.0, $bottomY - $startY);
        $lineH = $count > 0 ? min(28.0, max(17.5, $available / $count)) : 22.0;

        $frames = $this->contentFrames($templatePages);
        // Prefer second blank frame for interpretation when available.
        $frame = $frames[count($frames) > 1 ? 1 : 0];
        $this->addTemplatePage($pdf, $frame);
        $this->stampSectionHeading($pdf, $headingText, $x, $w, $headingY, $headingSize, $headingColor);
        $this->stampSectionHeading($pdf, $subText, $x, $w, $subY, $subSize, $metaColor);

        $y = $startY;
        foreach ($bodies as $body) {
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
            $pdf->Cell($w * 0.36, 4.2, sprintf('%s  %s', $glyph, $name), 0, 0, 'L', false, '', 0, false, 'T', 'M');

            $pdf->SetTextColor(...$metaColor);
            $pdf->SetFont('dejavusans', '', $bodySize);
            $pdf->SetXY($x + ($w * 0.36), $y);
            $pdf->Cell(
                $w * 0.64,
                4.2,
                trim(sprintf('%s %s  %s%s', $signGlyph, $sign, $degree, $rx)),
                0,
                0,
                'L'
            );

            $pdf->SetTextColor(...$noteColor);
            $pdf->SetFont('dejavusans', '', $noteSize);
            $pdf->SetXY($x, $y + 4.8);
            $pdf->MultiCell($w, 3.6, $note, 0, 'L', false, 1);

            $y += $lineH;
        }

        $this->stampContact($pdf);
        $this->stampSloganFooter($pdf);
    }

    private function stampContact(Fpdi $pdf): void
    {
        $cfg = is_array($this->layout['contact'] ?? null) ? $this->layout['contact'] : [];
        $x = (float) ($cfg['left_x'] ?? 36);
        $w = (float) ($cfg['width'] ?? 214);
        $y = (float) ($cfg['y'] ?? 308);
        $gap = (float) ($cfg['gap'] ?? 12);
        $size = (float) ($cfg['size'] ?? 10.5);
        $color = $this->rgb($cfg['color'] ?? [110, 62, 48]);
        $gold = $this->rgb($cfg['icon_gold'] ?? [201, 162, 39]);
        $iconSize = (float) ($cfg['icon_size'] ?? 4.2);
        $iconGap = (float) ($cfg['icon_gap'] ?? 2.4);
        $instagram = (string) ($cfg['instagram'] ?? '@astrologica5');
        $email = (string) ($cfg['email'] ?? 'astro.logica.uge@gmail.com');

        $pdf->SetFont('dejavusans', '', $size);
        $this->stampContactLine($pdf, $x, $w, $y, $instagram, $color, $gold, $iconSize, $iconGap, 'instagram');
        $this->stampContactLine($pdf, $x, $w, $y + $gap, $email, $color, $gold, $iconSize, $iconGap, 'email');
    }

    /**
     * Centered contact line with a small gold glyph to the left of the text.
     *
     * @param array{0:int,1:int,2:int} $textColor
     * @param array{0:int,1:int,2:int} $gold
     */
    private function stampContactLine(
        Fpdi $pdf,
        float $x,
        float $w,
        float $y,
        string $text,
        array $textColor,
        array $gold,
        float $iconSize,
        float $iconGap,
        string $glyph,
    ): void {
        $textW = $pdf->GetStringWidth($text);
        $total = $iconSize + $iconGap + $textW;
        $startX = $x + (($w - $total) / 2);
        $iconY = $y + 0.35;

        if ($glyph === 'instagram') {
            $this->drawInstagramGlyph($pdf, $startX, $iconY, $iconSize, $gold);
        } else {
            $this->drawEnvelopeGlyph($pdf, $startX, $iconY, $iconSize, $gold);
        }

        $pdf->SetTextColor(...$textColor);
        $pdf->SetXY($startX + $iconSize + $iconGap, $y);
        $pdf->Cell($textW + 1, 5, $text, 0, 0, 'L');
    }

    /**
     * Tiny Instagram-style camera glyph (rounded square + lens) in gold.
     *
     * @param array{0:int,1:int,2:int} $gold
     */
    private function drawInstagramGlyph(Fpdi $pdf, float $x, float $y, float $size, array $gold): void
    {
        $pdf->SetDrawColor(...$gold);
        $pdf->SetLineWidth(0.35);
        // Body
        $pdf->RoundedRect($x, $y, $size, $size, $size * 0.22, '1111', 'D');
        // Lens
        $cx = $x + ($size / 2);
        $cy = $y + ($size / 2);
        $r = $size * 0.22;
        $pdf->Circle($cx, $cy, $r, 0, 360, 'D');
        // Viewfinder dot
        $pdf->Circle($x + ($size * 0.72), $y + ($size * 0.28), $size * 0.06, 0, 360, 'F', [], $gold);
    }

    /**
     * Tiny envelope glyph in gold.
     *
     * @param array{0:int,1:int,2:int} $gold
     */
    private function drawEnvelopeGlyph(Fpdi $pdf, float $x, float $y, float $size, array $gold): void
    {
        $pdf->SetDrawColor(...$gold);
        $pdf->SetLineWidth(0.35);
        $h = $size * 0.72;
        $top = $y + (($size - $h) / 2);
        $pdf->Rect($x, $top, $size, $h, 'D');
        // Flap
        $pdf->Line($x, $top, $x + ($size / 2), $top + ($h * 0.55));
        $pdf->Line($x + $size, $top, $x + ($size / 2), $top + ($h * 0.55));
    }

    /**
     * Site slogan footer — content pages only (never pages 1–2).
     */
    private function stampSloganFooter(Fpdi $pdf): void
    {
        $cfg = is_array($this->layout['footer'] ?? null) ? $this->layout['footer'] : [];
        $slogan = (string) ($cfg['slogan'] ?? 'Con los Pies en la Tierra y el Corazón en las Estrellas');
        $brand = (string) ($cfg['brand'] ?? 'Astro-lógica');
        $x = (float) ($cfg['left_x'] ?? 36);
        $w = (float) ($cfg['width'] ?? 214);
        $y = (float) ($cfg['y'] ?? 342);

        $pdf->SetTextColor(...$this->rgb($cfg['brand_color'] ?? [28, 24, 68]));
        $pdf->SetFont('dejavusans', 'B', (float) ($cfg['brand_size'] ?? 8));
        $pdf->SetXY($x, $y);
        $pdf->Cell($w, 4, $brand, 0, 0, 'C');

        $pdf->SetTextColor(...$this->rgb($cfg['color'] ?? [90, 78, 64]));
        $pdf->SetFont('dejavusans', 'I', (float) ($cfg['size'] ?? 8.5));
        $pdf->SetXY($x, $y + 5);
        $pdf->Cell($w, 4, $slogan, 0, 0, 'C');
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
    private function formatBirthDateOnly(array $birth, string $locale): string
    {
        $y = (int) ($birth['year'] ?? 0);
        $m = (int) ($birth['month'] ?? 0);
        $d = (int) ($birth['day'] ?? 0);
        if ($y < 1 || $m < 1 || $d < 1) {
            return $locale === 'en' ? 'Date unknown' : 'Fecha desconocida';
        }
        try {
            $dt = new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $y, $m, $d));
        } catch (\Throwable) {
            return sprintf('%02d/%02d/%04d', $d, $m, $y);
        }
        if ($locale === 'en') {
            return $dt->format('F j, Y');
        }
        $months = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];
        $monthName = $months[(int) $dt->format('n')] ?? $dt->format('m');
        return sprintf('%d de %s de %s', (int) $dt->format('j'), $monthName, $dt->format('Y'));
    }

    /**
     * @param array<string, mixed> $birth
     */
    private function formatBirthTimeOnly(array $birth, string $locale): string
    {
        $h = (int) ($birth['hour'] ?? 0);
        $min = (int) ($birth['minute'] ?? 0);
        $label = $locale === 'en' ? 'Time' : 'Hora';
        return sprintf('%s · %02d:%02d', $label, $h, $min);
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
