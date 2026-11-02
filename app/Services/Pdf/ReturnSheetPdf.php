<?php

namespace App\Services\Pdf;

use App\Models\Order;
use Elibyy\TCPDF\Facades\TCPDF;

class ReturnSheetPdf extends OrderPdfDocument
{
    protected function fileSuffix(): string
    {
        return 'rueckgabe';
    }

    protected function relations(): array
    {
        return ['orderedItems.item', 'role', 'user', 'itemCollections'];
    }

    protected function render(Order $order): void
    {
        $this->begin('Retourenliste', 'Retourenliste', $order);

        $this->pdf->renderSectionTitle('Retoure');

        $columns = [
            ['width' => 12, 'label' => 'Pos.', 'align' => 'C'],
            ['width' => 88, 'label' => 'Bezeichnung', 'align' => 'L'],
            ['width' => 25, 'label' => 'Geliefert', 'align' => 'C'],
            ['width' => 25, 'label' => 'Retourniert', 'align' => 'C'],
            ['width' => 30, 'label' => 'Defekt', 'align' => 'C'],
        ];

        $this->pdf->renderTableHeader($columns);

        TCPDF::SetFont('helvetica', '', 10);
        $fill = false;
        foreach ($order->orderedItems as $index => $orderedItem) {
            $this->pdf->checkPageBreak(250, $columns);

            TCPDF::SetFillColor(250, 250, 250);
            TCPDF::Cell(12, 7, $index + 1, 1, 0, 'C', $fill);
            TCPDF::Cell(88, 7, ' '.$orderedItem->item->name, 1, 0, 'L', $fill);
            TCPDF::Cell(25, 7, $orderedItem->delivered, 1, 0, 'C', $fill);
            TCPDF::Cell(25, 7, '', 1, 0, 'C', $fill);
            TCPDF::Cell(30, 7, '', 1, 1, 'C', $fill);
            $fill = ! $fill;
        }

        $this->pdf->renderSignatures($order->user->fullName());
    }
}
