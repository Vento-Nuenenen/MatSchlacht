<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Order;
use App\States\Order\Committed;
use App\States\Order\Delivered;
use App\States\Order\OrderState;
use App\States\Order\Stored;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Map of the order form submit actions to the resulting order state.
     *
     * @var array<string, class-string<OrderState>>
     */
    protected const array ACTION_STATES = [
        'save' => Stored::class,
        'order' => Committed::class,
    ];

    /**
     * Create a new order from the order form.
     *
     * @param  array<string, mixed>  $data
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data): Order {
            $order = Order::create([
                ...$this->orderAttributes($data),
                'state' => $this->stateForAction($data['action'] ?? null) ?? Stored::class,
            ]);

            $this->syncPositions($order, $data);

            return $order;
        });
    }

    /**
     * Update an order from the order form and apply the requested submit action.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function updateOrder(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data): Order {
            $order->update($this->orderAttributes($data));
            $this->syncPositions($order, $data);

            $targetState = $this->stateForAction($data['action'] ?? null);

            if ($targetState !== null && ! $order->state->equals($targetState)) {
                $this->transition($order, $targetState);
            }

            return $order;
        });
    }

    /**
     * Update an order from the management form. Delivered orders only accept
     * returned quantities besides their basic information.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateManagedOrder(Order $order, array $data): Order
    {
        return DB::transaction(function () use ($order, $data): Order {
            $order->update($this->orderAttributes($data));

            if ($order->state->equals(Delivered::class)) {
                $this->syncReturnedItems($order, $data['returned'] ?? []);
            } else {
                $this->syncPositions($order, $data);
            }

            return $order;
        });
    }

    /**
     * Transition an order to another state, respecting the configured state machine.
     *
     * @throws ValidationException
     */
    public function transition(Order $order, mixed $targetState): void
    {
        if (! is_string($targetState) || ! $order->state->canTransitionTo($targetState)) {
            throw ValidationException::withMessages(['state' => 'Ungültiger Statusübergang.']);
        }

        $order->state->transitionTo($targetState);
    }

    /**
     * Sync returned quantities (given in lots) for delivered orders.
     *
     * @param  array<int|string, int|string|null>  $returnedData
     */
    public function syncReturnedItems(Order $order, array $returnedData): void
    {
        $items = Item::findMany(array_keys($returnedData))->keyBy('id');

        foreach ($returnedData as $itemId => $quantity) {
            $item = $items->get($itemId);
            $units = $item instanceof Item ? $item->unitsFor((int) $quantity) : (int) $quantity;

            $order->orderedItems()
                ->where('item_id', $itemId)
                ->update(['returned' => $units]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function orderAttributes(array $data): array
    {
        return [
            'event' => $data['event'],
            'event_start' => $data['event_start'],
            'event_end' => $data['event_end'],
            'role_id' => $data['role_id'],
            'user_id' => $data['user_id'],
        ];
    }

    /**
     * @return class-string<OrderState>|null
     */
    protected function stateForAction(mixed $action): ?string
    {
        return is_string($action) ? (self::ACTION_STATES[$action] ?? null) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function syncPositions(Order $order, array $data): void
    {
        if (isset($data['items'])) {
            $this->syncItems($order, $data['items']);
        }

        if (isset($data['collections'])) {
            $order->itemCollections()->sync($data['collections']);
        }
    }

    /**
     * Replace the ordered items. Quantities are given in lots and stored in units.
     *
     * @param  array<int|string, int|string|null>  $itemsData
     */
    protected function syncItems(Order $order, array $itemsData): void
    {
        $order->orderedItems()->delete();

        $quantities = array_filter(array_map('intval', $itemsData), fn (int $quantity): bool => $quantity > 0);
        $items = Item::findMany(array_keys($quantities))->keyBy('id');

        $positions = [];

        foreach ($quantities as $itemId => $quantity) {
            $item = $items->get($itemId);

            $positions[] = [
                'item_id' => (int) $itemId,
                'quantity' => $item instanceof Item ? $item->unitsFor($quantity) : $quantity,
                'confirmed' => false,
                'delivered' => false,
                'returned' => 0,
                'repair' => 0,
                'broken' => 0,
            ];
        }

        $order->orderedItems()->createMany($positions);
    }
}
