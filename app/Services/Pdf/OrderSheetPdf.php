<?php

namespace App\Services\Pdf;

use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\StorageUnit;
use App\Services\ComponentManager;
use Elibyy\TCPDF\Facades\TCPDF;
use Illuminate\Support\Collection;

class OrderSheetPdf extends OrderPdfDocument
{
    protected function fileSuffix(): string
    {
        return 'auftragsliste';
    }

    protected function relations(): array
    {
        $relations = ['orderedItems.item', 'role', 'user', 'itemCollections'];

        if ($this->isStorageEnabled()) {
            $relations[] = 'orderedItems.item.stocks.storageUnit.'.StorageUnit::ANCESTORS;
        }

        return $relations;
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

        $isStorageEnabled = $this->isStorageEnabled();

        $columns = $isStorageEnabled
            ? [
                ['width' => 12, 'label' => 'Pos.', 'align' => 'C'],
                ['width' => 63, 'label' => 'Bezeichnung', 'align' => 'L'],
                ['width' => 65, 'label' => 'Lagerplatz', 'align' => 'L'],
                ['width' => 20, 'label' => 'Bestellt', 'align' => 'C'],
                ['width' => 20, 'label' => 'Ausgeg.', 'align' => 'C'],
            ]
            : [
                ['width' => 15, 'label' => 'Pos.', 'align' => 'C'],
                ['width' => 105, 'label' => 'Bezeichnung', 'align' => 'L'],
                ['width' => 30, 'label' => 'Bestellt', 'align' => 'C'],
                ['width' => 30, 'label' => 'Ausgegeben', 'align' => 'C'],
            ];

        $this->pdf->renderTableHeader($columns);

        TCPDF::SetFont('helvetica', '', 10);
        $fill = false;
        $orderedItems = $isStorageEnabled ? $this->sortByStoragePath($order->orderedItems) : $order->orderedItems->values();
        $pdf = TCPDF::getFacadeRoot();

        foreach ($orderedItems as $index => $orderedItem) {
            $this->pdf->checkPageBreak(250, $columns);

            TCPDF::SetFillColor(250, 250, 250);
            TCPDF::Cell($columns[0]['width'], 7, $index + 1, 1, 0, 'C', $fill);
            $pdf->Cell($columns[1]['width'], 7, ' '.$orderedItem->item->name, 1, 0, 'L', $fill, '', 1);

            if ($isStorageEnabled) {
                TCPDF::SetFont('helvetica', '', 8);
                $pdf->Cell($columns[2]['width'], 7, ' '.$this->storageLabel($orderedItem), 1, 0, 'L', $fill, '', 1);
                TCPDF::SetFont('helvetica', '', 10);
            }

            TCPDF::Cell($columns[count($columns) - 2]['width'], 7, $orderedItem->quantity, 1, 0, 'C', $fill);
            TCPDF::Cell($columns[count($columns) - 1]['width'], 7, '', 1, 1, 'C', $fill);
            $fill = ! $fill;
        }

        $this->pdf->renderSignatures($order->user->fullName());
    }

    protected function isStorageEnabled(): bool
    {
        return app(ComponentManager::class)->isEnabled('storage');
    }

    /**
     * Sort the ordered items by their first bin, so items stored close to each other follow each other.
     * Items without a bin come last, sorted by name.
     *
     * @param  Collection<int, OrderedItem>  $orderedItems
     * @return Collection<int, OrderedItem>
     */
    public function sortByStoragePath(Collection $orderedItems): Collection
    {
        return $orderedItems->sort(function (OrderedItem $first, OrderedItem $second): int {
            $firstBin = $first->item->primaryStorageUnit();
            $secondBin = $second->item->primaryStorageUnit();

            if ($firstBin === null || $secondBin === null) {
                return [$firstBin === null, $first->item->name] <=> [$secondBin === null, $second->item->name];
            }

            return StorageUnit::compareByPath($firstBin, $secondBin) ?: strnatcasecmp($first->item->name, $second->item->name);
        })->values();
    }

    protected function storageLabel(OrderedItem $orderedItem): string
    {
        $bin = $orderedItem->item->primaryStorageUnit();

        if ($bin === null) {
            return '–';
        }

        $furtherBins = $orderedItem->item->stocks->count() - 1;

        return $bin->path().($furtherBins > 0 ? " (+{$furtherBins})" : '');
    }
}
