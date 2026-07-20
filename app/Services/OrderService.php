<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Create or update an order with items and collections.
     *
     * @param  array<string, mixed>  $data
     */
    public function syncOrder(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data) {
            $order->fill([
                'event' => $data['event'],
                'event_start' => $data['event_start'],
                'event_end' => $data['event_end'],
                'role_id' => $data['role_id'],
                'user_id' => $data['user_id'],
            ]);

            if (isset($data['status'])) {
                $order->state = $data['status'];
            }

            $order->save();

            if (isset($data['items'])) {
                $this->syncItems($order, $data['items']);
            }

            if (isset($data['collections'])) {
                $order->itemCollections()->sync($data['collections']);
            }

            return $order;
        });
    }

    /**
     * Sync returned quantities for delivered orders.
     *
     * @param  array<int, int>  $returnedData
     */
    public function syncReturnedItems(Order $order, array $returnedData): void
    {
        DB::transaction(function () use ($order, $returnedData) {
            foreach ($returnedData as $itemId => $qty) {
                $qty = (int) $qty;
                $item = Item::find($itemId);

                if ($item && $item->lot > 1) {
                    $qty = $qty * $item->lot;
                }

                $order->orderedItems()
                    ->where('item_id', $itemId)
                    ->update(['returned' => $qty]);
            }
        });
    }

    /**
     * @param  array<int, int>  $itemsData
     */
    protected function syncItems(Order $order, array $itemsData): void
    {
        $order->orderedItems()->delete();

        foreach ($itemsData as $itemId => $qty) {
            $qty = (int) $qty;

            if ($qty > 0) {
                $item = Item::find($itemId);

                if ($item && $item->lot > 1) {
                    $qty = $qty * $item->lot;
                }

                $order->orderedItems()->create([
                    'item_id' => (int) $itemId,
                    'quantity' => $qty,
                    'confirmed' => false,
                    'delivered' => false,
                    'returned' => 0,
                    'repair' => 0,
                    'broken' => 0,
                ]);
            }
        }
    }
}
