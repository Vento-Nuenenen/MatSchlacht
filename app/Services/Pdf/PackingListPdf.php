<?php

namespace App\Services\Pdf;

use App\Models\Order;
use Elibyy\TCPDF\Facades\TCPDF;

class PackingListPdf extends OrderPdfDocument
{
    protected function fileSuffix(): string
    {
        return 'packliste';
    }

    protected function relations(): array
    {
        return ['orderedItems.item.contents', 'role', 'user'];
    }

    protected function render(Order $order): void
    {
        $this->begin(__('Packliste'), __('Packliste (Inhalte)'), $order);

        $this->pdf->renderSectionTitle(__('Inhaltsübersicht'));

        $itemsWithContents = $order->orderedItems->filter(
            fn ($orderedItem): bool => $orderedItem->item->contents->isNotEmpty()
        );

        if ($itemsWithContents->isEmpty()) {
            TCPDF::SetFont('helvetica', 'I', 10);
            TCPDF::Cell(180, 10, __('Keine Materialien mit Inhalten in dieser Bestellung gefunden.'), 0, 1, 'L');

            return;
        }

        $columns = [
            ['width' => 150, 'label' => __('Material / Inhalt'), 'align' => 'L'],
            ['width' => 30, 'label' => __('Anzahl / Menge'), 'align' => 'C'],
        ];

        $this->pdf->renderTableHeader($columns);

        TCPDF::SetFont('helvetica', '', 10);
        $fill = false;

        foreach ($itemsWithContents as $orderedItem) {
            $this->pdf->checkPageBreak(250, $columns);

            TCPDF::SetFont('helvetica', 'B', 10);
            TCPDF::SetFillColor(245, 245, 245);
            TCPDF::Cell(150, 7, ' '.$orderedItem->item->name, 1, 0, 'L', true);
            TCPDF::Cell(30, 7, $orderedItem->quantity, 1, 1, 'C', true);

            TCPDF::SetFont('helvetica', '', 9);
            foreach ($orderedItem->item->contents as $content) {
                $this->pdf->checkPageBreak(250, $columns);
                TCPDF::Cell(150, 6, '    - '.$content->name, 1, 0, 'L', $fill);
                TCPDF::Cell(30, 6, $content->quantity, 1, 1, 'C', $fill);
                $fill = ! $fill;
            }
            TCPDF::Ln(2);
        }
    }
}
