<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Order;
use App\Models\OrderedItem;
use App\States\Order\OrderState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Works out how much of an item can still be ordered for a period: the usable stock minus what
 * reserving orders (submitted, accepted, handed out) need in an overlapping period.
 */
class StockAvailability
{
    /**
     * The units reserved by other orders overlapping the period, keyed by item id.
     *
     * @param  array<int, int>  $itemIds
     * @return Collection<int, int>
     */
    public function reservedUnits(array $itemIds, string $start, string $end, ?Order $exceptOrder = null): Collection
    {
        return OrderedItem::query()
            ->whereIn('item_id', $itemIds)
            ->whereHas('order', fn (Builder $query) => $query
                ->whereState('state', OrderState::RESERVING_STATES)
                ->whereDate('event_start', '<=', $end)
                ->whereDate('event_end', '>=', $start)
                ->when($exceptOrder, fn (Builder $query, Order $order) => $query->whereKeyNot($order->getKey())))
            ->get(['item_id', 'quantity'])
            ->groupBy('item_id')
            ->map(fn (Collection $positions): int => (int) $positions->sum('quantity'));
    }

    public function availableUnits(Item $item, int $reservedUnits): int
    {
        return max(0, $item->usableStock() - $reservedUnits);
    }

    /**
     * What the order needs more than is available, as messages for the user.
     *
     * @return array<int, string>
     */
    public function shortages(Order $order): array
    {
        $positions = $order->orderedItems()->with('item')->get();
        $reserved = $this->reservedUnits($positions->pluck('item_id')->all(), (string) $order->event_start, (string) $order->event_end, $order);
        $shortages = [];

        foreach ($positions as $position) {
            $available = $this->availableUnits($position->item, $reserved->get($position->item_id, 0));

            if ($position->quantity > $available) {
                $shortages[] = self::shortageMessage($position->item, $available, $position->quantity);
            }
        }

        return $shortages;
    }

    public static function shortageMessage(Item $item, int $available, int $requested): string
    {
        return __('Vom Artikel \':name\' sind im gewählten Zeitraum nur noch :available Stück verfügbar (angefordert: :requested).', ['name' => $item->name, 'available' => $available, 'requested' => $requested]);
    }
}
