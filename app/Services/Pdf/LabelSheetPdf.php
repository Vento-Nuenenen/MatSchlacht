<?php

namespace App\Services\Pdf;

use App\Services\ComponentManager;
use App\Services\PdfService;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Http\Response;

/**
 * Renders labels with a QR code onto A4 sheets with 3 × 8 labels of 70 × 37 mm (e.g. Herma 4453, Avery 3475).
 */
class LabelSheetPdf
{
    public const COLUMNS = 3;

    public const ROWS = 8;

    public const LABELS_PER_SHEET = self::COLUMNS * self::ROWS;

    protected const LABEL_WIDTH = 70.0;

    protected const LABEL_HEIGHT = 37.0;

    protected const SHEET_TOP = 0.5;

    protected const PADDING = 3.0;

    protected const QR_SIZE = 31.0;

    public function __construct(
        protected PdfService $pdf,
        protected ComponentManager $components,
    ) {}

    /**
     * Render the labels and return them as a PDF response.
     *
     * @param  iterable<int, Label>  $labels
     * @param  int  $skip  Number of labels already used on the first sheet.
     */
    public function download(iterable $labels, string $filename, int $skip = 0): Response
    {
        TCPDF::reset();
        TCPDF::SetTitle('Etiketten');
        TCPDF::SetAuthor('MatSchlacht');
        TCPDF::setPrintHeader(false);
        TCPDF::setPrintFooter(false);
        TCPDF::SetMargins(0, 0, 0);
        TCPDF::SetAutoPageBreak(false);
        TCPDF::setCellPaddings(0, 0, 0, 0);

        $caption = $this->components->setting('storage', 'label_caption') ?? config('app.name');
        $position = max(0, $skip) % self::LABELS_PER_SHEET;
        TCPDF::AddPage('P', 'A4');

        foreach ($labels as $label) {
            if ($position === self::LABELS_PER_SHEET) {
                TCPDF::AddPage('P', 'A4');
                $position = 0;
            }

            $this->renderLabel(
                $label,
                (string) $caption,
                ($position % self::COLUMNS) * self::LABEL_WIDTH,
                self::SHEET_TOP + intdiv($position, self::COLUMNS) * self::LABEL_HEIGHT,
            );

            $position++;
        }

        return response($this->pdf->output($filename), 200)
            ->header('Content-Type', 'application/pdf');
    }

    protected function renderLabel(Label $label, string $caption, float $x, float $y): void
    {
        TCPDF::write2DBarcode($label->url, 'QRCODE,M', $x + self::PADDING, $y + self::PADDING, self::QR_SIZE, self::QR_SIZE, [
            'border' => false,
            'padding' => 0,
            'fgcolor' => [0, 0, 0],
            'bgcolor' => false,
        ], 'N');

        $textX = $x + self::PADDING * 2 + self::QR_SIZE;
        $textWidth = self::LABEL_WIDTH - self::QR_SIZE - self::PADDING * 3;

        TCPDF::SetTextColor(110, 110, 110);
        TCPDF::SetFont('helvetica', '', 6.5);
        $this->textBox($textWidth, 3, mb_strtoupper($label->kind), 0, 'L', false, 1, $textX, $y + self::PADDING, true, 0, false, true, 3);

        TCPDF::SetTextColor(0, 0, 0);
        TCPDF::SetFont('helvetica', 'B', 11);
        $this->textBox($textWidth, 12, $label->title, 0, 'L', false, 1, $textX, $y + self::PADDING + 3.5, true, 0, false, true, 12, 'T', true);

        TCPDF::SetFont('helvetica', '', 7);
        $this->textBox($textWidth, 11, $label->subtitle, 0, 'L', false, 1, $textX, $y + self::PADDING + 16, true, 0, false, true, 11, 'T', true);

        TCPDF::SetTextColor(110, 110, 110);
        TCPDF::SetFont('helvetica', 'I', 6);
        $this->textBox($textWidth, 3, $caption, 0, 'L', false, 1, $textX, $y + self::LABEL_HEIGHT - self::PADDING - 3, true, 0, false, true, 3);
    }

    /**
     * Write text clipped to a box. The facade docblock only knows the first six MultiCell parameters,
     * so the clipping options (max height, fit to cell) are passed to the TCPDF instance directly.
     */
    protected function textBox(float $width, float $height, string $text, int $border, string $align, bool $fill, int $ln, float $x, float $y, bool $resetHeight, int $stretch, bool $isHtml, bool $autoPadding, float $maxHeight, string $verticalAlign = 'T', bool $fitCell = false): void
    {
        TCPDF::getFacadeRoot()->MultiCell($width, $height, $text, $border, $align, $fill, $ln, $x, $y, $resetHeight, $stretch, $isHtml, $autoPadding, $maxHeight, $verticalAlign, $fitCell);
    }
}
