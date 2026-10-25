<?php

declare(strict_types=1);

namespace Clubmanager\Libraries;

/**
 * Pure PHP PDF Document Builder
 *
 * Standalone PDF 1.4 generator compatible with FPDF conventions.
 * Produces crisp, publication-ready vector PDF documents with custom styling,
 * headers, footers, and grid tables without requiring external binaries or composer packages.
 *
 * @package Clubmanager\Libraries
 * @author  Reinhard Schiessl
 */
class PdfBuilder
{
    protected int $page = 0;
    protected int $n = 2;
    protected array $offsets = [];
    protected string $buffer = '';
    protected array $pages = [];
    protected float $w = 210.0;
    protected float $h = 297.0;
    protected float $x = 10.0;
    protected float $y = 10.0;
    protected float $leftMargin = 15.0;
    protected float $topMargin = 15.0;
    protected float $rightMargin = 15.0;
    protected float $bottomMargin = 15.0;
    protected float $fontSizePt = 12.0;
    protected float $fontSize = 12.0;
    protected string $fontFamily = 'Helvetica';
    protected string $fontStyle = '';
    protected string $drawColor = '0 G';
    protected string $fillColor = '0 g';
    protected string $textColor = '0 g';
    protected string $title = 'Clubmanager Report';
    protected string $author = 'Reinhard Schiessl';

    public function __construct(string $orientation = 'P', string $unit = 'mm', string $size = 'A4')
    {
        if (strtoupper($orientation) === 'L') {
            $this->w = 297.0;
            $this->h = 210.0;
        } else {
            $this->w = 210.0;
            $this->h = 297.0;
        }
        $this->x = $this->leftMargin;
        $this->y = $this->topMargin;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function addPage(): void
    {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->x = $this->leftMargin;
        $this->y = $this->topMargin;
    }

    public function setFont(string $family, string $style = '', float $size = 12.0): void
    {
        $family = strtolower($family);
        if ($family === 'arial' || $family === 'sans-serif') {
            $family = 'helvetica';
        }
        $this->fontFamily = ucfirst($family);
        $this->fontStyle = strtoupper($style);
        $this->fontSizePt = $size;
        $this->fontSize = $size / 2.83464567; // points to mm conversion
    }

    public function setTextColor(int $r, int $g = -1, int $b = -1): void
    {
        if ($g === -1 && $b === -1) {
            $this->textColor = sprintf('%.3F g', $r / 255);
        } else {
            $this->textColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
        }
    }

    public function setFillColor(int $r, int $g = -1, int $b = -1): void
    {
        if ($g === -1 && $b === -1) {
            $this->fillColor = sprintf('%.3F g', $r / 255);
        } else {
            $this->fillColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
        }
    }

    public function setDrawColor(int $r, int $g = -1, int $b = -1): void
    {
        if ($g === -1 && $b === -1) {
            $this->drawColor = sprintf('%.3F G', $r / 255);
        } else {
            $this->drawColor = sprintf('%.3F %.3F %.3F RG', $r / 255, $g / 255, $b / 255);
        }
    }

    public function setX(float $x): void
    {
        $this->x = $x;
    }

    public function setY(float $y): void
    {
        $this->y = $y;
    }

    public function getX(): float
    {
        return $this->x;
    }

    public function getY(): float
    {
        return $this->y;
    }

    public function ln(?float $h = null): void
    {
        $this->x = $this->leftMargin;
        $this->y += ($h !== null) ? $h : ($this->fontSize * 1.5);
    }

    public function cell(float $w, float $h = 0, string $txt = '', mixed $border = 0, int $ln = 0, string $align = 'L', bool $fill = false): void
    {
        if ($this->page === 0) {
            $this->addPage();
        }

        // Auto page-break check
        if ($this->y + $h > $this->h - $this->bottomMargin) {
            $this->addPage();
        }

        if ($w == 0) {
            $w = $this->w - $this->rightMargin - $this->x;
        }

        $s = '';
        if ($fill || $border == 1) {
            $op = ($fill && $border == 1) ? 'B' : ($fill ? 'f' : 'S');
            $s .= sprintf('q %s %s %.2F %.2F %.2F %.2F re %s Q ', $this->drawColor, $this->fillColor, $this->x * 2.83464567, ($this->h - $this->y - $h) * 2.83464567, $w * 2.83464567, $h * 2.83464567, $op);
        }

        if ($txt !== '') {
            $width = $this->getStringWidth($txt);
            $dx = 2.0; // Left padding
            if ($align === 'R') {
                $dx = $w - $width - 2.0;
            } elseif ($align === 'C') {
                $dx = ($w - $width) / 2.0;
            }

            // Clean text encoding for standard PDF Helvetica font (Windows-1252 / ISO-8859-1)
            $encodedText = mb_convert_encoding($txt, 'ISO-8859-1', 'UTF-8');
            $escaped = strtr($encodedText, [')' => '\\)', '(' => '\\(', '\\' => '\\\\']);

            $fontKey = 'F1';
            if ($this->fontStyle === 'B') {
                $fontKey = 'F2';
            } elseif ($this->fontStyle === 'I') {
                $fontKey = 'F3';
            }

            $tx = ($this->x + $dx) * 2.83464567;
            $ty = ($this->h - ($this->y + 0.5 * $h + 0.3 * $this->fontSize)) * 2.83464567;

            $s .= sprintf('BT %s /%s %.2F Tf %.2F %.2F Td (%s) Tj ET ', $this->textColor, $fontKey, $this->fontSizePt, $tx, $ty, $escaped);
        }

        $this->pages[$this->page] .= $s;

        if ($ln > 0) {
            $this->y += $h;
            $this->x = $this->leftMargin;
        } else {
            $this->x += $w;
        }
    }

    public function getStringWidth(string $s): float
    {
        return mb_strlen($s) * ($this->fontSizePt * 0.22);
    }

    public function line(float $x1, float $y1, float $x2, float $y2): void
    {
        $k = 2.83464567;
        $this->pages[$this->page] .= sprintf('q %s %.2F w %.2F %.2F m %.2F %.2F l S Q ', $this->drawColor, 0.5, $x1 * $k, ($this->h - $y1) * $k, $x2 * $k, ($this->h - $y2) * $k);
    }

    public function output(string $dest = 'I', string $name = 'doc.pdf'): string
    {
        $this->buildPdf();
        if ($dest === 'S') {
            return $this->buffer;
        }

        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $name . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $this->buffer;
        exit;
    }

    protected function buildPdf(): void
    {
        $this->buffer = "%PDF-1.4\n";

        // Object 1: Catalog
        $this->newObj();
        $this->buffer .= "<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // Object 2: Pages root
        $this->newObj();
        $kids = '';
        for ($i = 1; $i <= $this->page; $i++) {
            $kids .= (2 + $i) . " 0 R ";
        }
        $k = 2.83464567;
        $this->buffer .= sprintf("<< /Type /Pages /Kids [ %s] /Count %d /MediaBox [ 0 0 %.2F %.2F ] >>\nendobj\n", $kids, $this->page, $this->w * $k, $this->h * $k);

        // Page objects
        $contentObjIds = [];
        $nextObjId = 2 + $this->page + 1;

        for ($i = 1; $i <= $this->page; $i++) {
            $contentId = $nextObjId++;
            $contentObjIds[$i] = $contentId;
            $this->newObj();
            $this->buffer .= sprintf(
                "<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 %d 0 R /F2 %d 0 R /F3 %d 0 R >> /ProcSet [ /PDF /Text /ImageC ] >> /Contents %d 0 R >>\nendobj\n",
                $nextObjId,
                $nextObjId + 1,
                $nextObjId + 2,
                $contentId
            );
        }

        // Content stream objects
        for ($i = 1; $i <= $this->page; $i++) {
            $stream = $this->pages[$i];
            $this->newObj();
            $this->buffer .= sprintf("<< /Length %d >>\nstream\n%s\nendstream\nendobj\n", strlen($stream), $stream);
        }

        // Font 1: Helvetica Regular
        $this->newObj();
        $this->buffer .= "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj\n";

        // Font 2: Helvetica-Bold
        $this->newObj();
        $this->buffer .= "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>\nendobj\n";

        // Font 3: Helvetica-Oblique
        $this->newObj();
        $this->buffer .= "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>\nendobj\n";

        // Cross-reference table
        $xrefOffset = strlen($this->buffer);
        $totalObjs = $this->n - 1;
        $this->buffer .= "xref\n0 " . ($totalObjs + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $totalObjs; $i++) {
            $this->buffer .= sprintf("%010d 00000 n \n", $this->offsets[$i]);
        }

        // Trailer
        $this->buffer .= sprintf("trailer\n<< /Size %d /Root 1 0 R >>\nstartxref\n%d\n%%%%EOF\n", $totalObjs + 1, $xrefOffset);
    }

    protected function newObj(): void
    {
        $this->n++;
        $this->offsets[$this->n - 1] = strlen($this->buffer);
        $this->buffer .= ($this->n - 1) . " 0 obj\n";
    }
}
