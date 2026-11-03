<?php

namespace App\Observers;

use App\Models\ItemStock;

class ItemStockObserver
{
    /**
     * Keep the total stock of the item in line with its bins.
     */
    public function saved(ItemStock $itemStock): void
    {
        $itemStock->item->syncStockFromStorage();
    }

    /**
     * Keep the total stock of the item in line with its remaining bins; material taken out of the last bin is gone.
     */
    public function deleted(ItemStock $itemStock): void
    {
        $itemStock->item->syncStockFromStorage(evenWithoutBins: true);
    }
}
