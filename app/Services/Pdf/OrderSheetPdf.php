<?php

namespace App\Services\Pdf;

use App\Models\Order;
use Elibyy\TCPDF\Facades\TCPDF;

class OrderSheetPdf extends OrderPdfDocument
{
    protected function fileSuffix(): string
    {
        return 'auftragsliste';
    }

    protected function relations(): array
    {
        return ['orderedItems.item', 'role', 'user', 'itemCollections'];
    }

    protected function render(Order $order): void
    {
        $this->begin('Auftragsliste', 'Auftragsliste', $order);

        $this->pdf->renderSectionTitle('Materialkollektionen');
        TCPDF::SetFont('helvetica', '', 10);

        if ($order->itemCollections->isNotEmpty()) {
            $collections = $order->itemCollections->groupBy('id')->map(function ($group): string {
                $count = $group->count();

                return $count > 1 ? $group->first()->name.' ('.$count.'x)' : (string) $group->first()->name;
            })->implode(', ');
            TCPDF::MultiCell(0, 7, $collections, 0, 'L');
        } else {
            TCPDF::Cell(0, 7, 'Es wurden keine Materialkollektionen angefordert.', 0, 1, 'L');
        }
        TCPDF::Ln(5);

        $this->pdf->renderSectionTitle('Ausgegebene Materialien');

        $columns = [
            ['width' => 15, 'label' => 'Pos.', 'align' => 'C'],
            ['width' => 105, 'label' => 'Bezeichnung', 'align' => 'L'],
            ['width' => 30, 'label' => 'Bestellt', 'align' => 'C'],
            ['width' => 30, 'label' => 'Ausgegeben', 'align' => 'C'],
        ];

        $this->pdf->renderTableHeader($columns);

        TCPDF::SetFont('helvetica', '', 10);
        $fill = false;
        foreach ($order->orderedItems as $index => $orderedItem) {
            $this->pdf->checkPageBreak(250, $columns);

            TCPDF::SetFillColor(250, 250, 250);
            TCPDF::Cell(15, 7, $index + 1, 1, 0, 'C', $fill);
            TCPDF::Cell(105, 7, ' '.$orderedItem->item->name, 1, 0, 'L', $fill);
            TCPDF::Cell(30, 7, $orderedItem->quantity, 1, 0, 'C', $fill);
            TCPDF::Cell(30, 7, '', 1, 1, 'C', $fill);
            $fill = ! $fill;
        }

        $this->pdf->renderSignatures($order->user->fullName());
    }
}
