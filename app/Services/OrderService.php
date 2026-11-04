<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Order;
use App\Models\OrderedItem;
use App\States\Order\Accepted;
use App\States\Order\Committed;
use App\States\Order\Delivered;
use App\States\Order\OrderState;
use App\States\Order\Stored;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        protected StockAvailability $availability,
        protected ComponentManager $components,
    ) {}

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

            if ($order->state->reservesStock()) {
                $this->ensureAvailable($order);
            }

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
     * Transition an order to another state, respecting the configured state machine. An order
     * that starts to reserve material needs it to be available, handing out records the quantities.
     *
     * @throws ValidationException
     */
    public function transition(Order $order, mixed $targetState): void
    {
        if (! is_string($targetState) || ! $order->state->canTransitionTo($targetState)) {
            throw ValidationException::withMessages(['state' => 'Ungültiger Statusübergang.']);
        }

        DB::transaction(function () use ($order, $targetState): void {
            $targetClass = OrderState::resolveStateClass($targetState);

            if (in_array($targetClass, OrderState::RESERVING_STATES, true) && ! $order->state->reservesStock()) {
                $this->ensureAvailable($order);
            }

            $wasHandedOut = $order->state->equals(Delivered::class);
            $order->state->transitionTo($targetState);

            if ($targetClass === Delivered::class) {
                $order->orderedItems()->update(['delivered' => DB::raw('quantity')]);
            } elseif ($wasHandedOut && $targetClass === Accepted::class) {
                $order->orderedItems()->update(['delivered' => 0]);
            }
        });
    }

    /**
     * @throws ValidationException
     */
    protected function ensureAvailable(Order $order): void
    {
        // Serializes concurrent orders of the same material until this transaction ends.
        Item::whereKey($order->orderedItems()->pluck('item_id'))->lockForUpdate()->get();

        $shortages = $this->availability->shortages($order);

        if ($shortages !== []) {
            throw ValidationException::withMessages(['items' => $shortages]);
        }
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

        if (isset($data['collections']) && $this->components->isEnabled('item_collections')) {
            // Not sync(): a collection can be part of an order several times.
            $order->itemCollections()->detach();
            $order->itemCollections()->attach($data['collections']);
        }
    }

    /**
     * Match the positions to the submitted quantities, given in lots and stored in units.
     * Existing positions are kept with what was recorded on them.
     *
     * @param  array<int|string, int|string|null>  $itemsData
     */
    protected function syncItems(Order $order, array $itemsData): void
    {
        $quantities = array_filter(array_map(intval(...), $itemsData), fn (int $quantity): bool => $quantity > 0);
        $items = Item::findMany(array_keys($quantities))->keyBy('id');

        $order->orderedItems()->whereNotIn('item_id', array_keys($quantities))->delete();
        $positions = $order->orderedItems()->get()->keyBy('item_id');

        foreach ($quantities as $itemId => $quantity) {
            $item = $items->get($itemId);
            $units = $item instanceof Item ? $item->unitsFor($quantity) : $quantity;
            $position = $positions->get($itemId);

            if ($position instanceof OrderedItem) {
                $position->update(['quantity' => $units]);

                continue;
            }

            $order->orderedItems()->create([
                'item_id' => (int) $itemId,
                'quantity' => $units,
                'delivered' => 0,
                'returned' => 0,
                'repair' => 0,
                'broken' => 0,
            ]);
        }
    }
}
