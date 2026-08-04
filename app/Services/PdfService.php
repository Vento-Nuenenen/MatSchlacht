<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Elibyy\TCPDF\Facades\TCPDF;

class PdfService
{
    /**
     * Initialize a new PDF document with standard settings.
     */
    public function init(string $title): void
    {
        TCPDF::SetTitle($title);
        TCPDF::SetAuthor('MatSchlacht');
        TCPDF::SetMargins(15, 20, 15);
        TCPDF::SetAutoPageBreak(true, 25);
        TCPDF::setPrintHeader(false);
        TCPDF::setPrintFooter(false);
        TCPDF::AddPage();
    }

    /**
     * Render the document title and date.
     */
    public function renderHeader(string $title, string $event): void
    {
        TCPDF::SetFont('helvetica', 'B', 18);
        TCPDF::Cell(130, 10, $title, 0, 0, 'L');
        TCPDF::SetFont('helvetica', '', 10);
        TCPDF::Cell(50, 10, 'Datum: '.date('d.m.Y'), 0, 1, 'R');

        TCPDF::SetFont('helvetica', 'B', 22);
        TCPDF::Cell(0, 15, $event, 0, 1, 'L');
        TCPDF::Ln(5);
    }

    /**
     * Render order information section.
     */
    public function renderOrderInfo(Order $order): void
    {
        TCPDF::SetFillColor(240, 240, 240);
        TCPDF::SetFont('helvetica', 'B', 12);
        TCPDF::Cell(0, 8, ' Bestellinformationen', 0, 1, 'L', true);
        TCPDF::Ln(2);

        $this->renderInfoRow('Einheit:', $order->role->name);
        $this->renderInfoRow('Bestellt von:', $order->user->fullName());

        $period = Carbon::parse($order->event_start)->format('d.m.Y').' bis '.Carbon::parse($order->event_end)->format('d.m.Y');
        $this->renderInfoRow('Zeitraum:', $period);
        TCPDF::Ln(5);
    }

    /**
     * Helper to render a label/value row.
     */
    protected function renderInfoRow(string $label, string $value): void
    {
        TCPDF::SetFont('helvetica', 'B', 10);
        TCPDF::Cell(40, 7, $label, 0, 0, 'L');
        TCPDF::SetFont('helvetica', '', 10);
        TCPDF::Cell(0, 7, $value, 0, 1, 'L');
    }

    /**
     * Render section title.
     */
    public function renderSectionTitle(string $title): void
    {
        TCPDF::SetFillColor(240, 240, 240);
        TCPDF::SetFont('helvetica', 'B', 12);
        TCPDF::Cell(0, 8, ' '.$title, 0, 1, 'L', true);
        TCPDF::Ln(2);
    }

    /**
     * Render table header.
     *
     * @param  array<int, array{width: int, label: string, align: string}>  $columns
     */
    public function renderTableHeader(array $columns): void
    {
        TCPDF::SetFillColor(220, 220, 220);
        TCPDF::SetFont('helvetica', 'B', 10);

        foreach ($columns as $index => $column) {
            $isLast = ($index === count($columns) - 1);
            TCPDF::Cell($column['width'], 8, $column['label'], 1, $isLast ? 1 : 0, $column['align'], true);
        }
    }

    /**
     * Check for page break and redraw header if necessary.
     *
     * @param  array<int, array{width: int, label: string, align: string}>  $columns
     */
    public function checkPageBreak(int $limit, array $columns): void
    {
        if (TCPDF::GetY() > $limit) {
            TCPDF::AddPage();
            $this->renderTableHeader($columns);
            TCPDF::SetFont('helvetica', '', 10);
        }
    }

    /**
     * Render signature section.
     */
    public function renderSignatures(string $userName): void
    {
        $y = TCPDF::GetY();
        if ($y > 240) {
            TCPDF::AddPage();
            $y = 20;
        } else {
            $y += 25;
        }

        TCPDF::SetY($y);
        TCPDF::Line(15, $y, 95, $y);
        TCPDF::Line(115, $y, 195, $y);

        TCPDF::SetY($y + 2);
        TCPDF::SetFont('helvetica', '', 10);
        TCPDF::Cell(80, 5, 'Unterschrift Materialwart', 0, 0, 'C');
        TCPDF::Cell(20, 5, '', 0, 0, 'C');
        TCPDF::Cell(80, 5, 'Unterschrift Besteller ('.$userName.')', 0, 1, 'C');
    }

    /**
     * Output the PDF.
     */
    public function output(string $filename): string
    {
        /** @var string|TCPDF $output */
        $output = TCPDF::Output($filename, 'S');

        return (string) $output;
    }
}
