<?php

use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\Order;
use App\Models\OrderedItem;
use App\Models\User;
use App\Services\OrderService;
use App\States\Order\Accepted;
use App\States\Order\Archived;
use App\States\Order\Committed;
use App\States\Order\Delivered;
use App\States\Order\Returned;
use App\States\Order\Stored;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Mail::fake();
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->role = Role::findByName('Wolfsstufe');
    $this->leader = User::factory()->create();
    $this->leader->assignRole($this->role);

    $this->materialwart = User::factory()->create();
    $this->materialwart->assignRole('Materialwart');

    $this->item = Item::factory()->create(['stock' => 10, 'lot' => 1, 'name' => 'Spatz-Zelt']);
});

/**
 * @param  class-string  $state
 */
function orderWithItem(Item $item, int $units, string $state, string $start = '2030-07-10', string $end = '2030-07-20'): Order
{
    $order = Order::factory()->create([
        'user_id' => test()->leader->id,
        'role_id' => test()->role->id,
        'state' => $state,
        'event_start' => $start,
        'event_end' => $end,
    ]);

    OrderedItem::factory()->create(['order_id' => $order->id, 'item_id' => $item->id, 'quantity' => $units]);

    return $order;
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function availabilityPayload(array $overrides = []): array
{
    return array_merge([
        'event' => 'Sommerlager',
        'event_start' => '2030-07-15',
        'event_end' => '2030-07-25',
        'role_id' => test()->role->id,
        'user_id' => test()->leader->id,
        'items' => [test()->item->id => 5],
        'action' => 'order',
    ], $overrides);
}

test('material reserved by another order in the period cannot be ordered again', function (string $state) {
    orderWithItem($this->item, 8, $state);

    $this->actingAs($this->leader)
        ->post(route('orders.store'), availabilityPayload())
        ->assertSessionHasErrors(['items' => "Vom Artikel 'Spatz-Zelt' sind im gewählten Zeitraum nur noch 2 Stück verfügbar (angefordert: 5)."]);

    expect(Order::count())->toBe(1);
})->with([
    'submitted' => [Committed::class],
    'accepted' => [Accepted::class],
    'handed out' => [Delivered::class],
]);

test('orders that do not reserve or do not overlap leave the material available', function (string $state, string $start, string $end) {
    orderWithItem($this->item, 8, $state, $start, $end);

    $this->actingAs($this->leader)
        ->post(route('orders.store'), availabilityPayload())
        ->assertSessionHasNoErrors();

    expect(Order::count())->toBe(2);
})->with([
    'draft' => [Stored::class, '2030-07-10', '2030-07-20'],
    'returned' => [Returned::class, '2030-07-10', '2030-07-20'],
    'archived' => [Archived::class, '2030-07-10', '2030-07-20'],
    'ending the day before' => [Committed::class, '2030-07-01', '2030-07-14'],
    'starting the day after' => [Committed::class, '2030-07-26', '2030-07-30'],
]);

test('orders touching on the same day overlap', function () {
    orderWithItem($this->item, 8, Committed::class, '2030-07-01', '2030-07-15');

    $this->actingAs($this->leader)
        ->post(route('orders.store'), availabilityPayload())
        ->assertSessionHasErrors('items');
});

test('material in repair is not available', function () {
    $this->item->update(['repair' => 7]);

    $this->actingAs($this->leader)
        ->post(route('orders.store'), availabilityPayload())
        ->assertSessionHasErrors('items');
});

test('drafts are only checked against the usable stock', function () {
    orderWithItem($this->item, 8, Committed::class);

    $this->actingAs($this->leader)
        ->post(route('orders.store'), availabilityPayload(['action' => 'save']))
        ->assertSessionHasNoErrors();

    $this->actingAs($this->leader)
        ->post(route('orders.store'), availabilityPayload(['action' => 'save', 'items' => [$this->item->id => 11]]))
        ->assertSessionHasErrors('items.'.$this->item->id);
});

test('submitting a draft fails when the material is taken in the meantime', function () {
    orderWithItem($this->item, 8, Committed::class);
    $draft = orderWithItem($this->item, 5, Stored::class, '2030-07-15', '2030-07-25');

    $this->actingAs($this->materialwart)
        ->post(route('orders.manage.transition', $draft), ['to' => 'committed'])
        ->assertSessionHas('error', "Vom Artikel 'Spatz-Zelt' sind im gewählten Zeitraum nur noch 2 Stück verfügbar (angefordert: 5).");

    expect($draft->fresh()->state)->toBeInstanceOf(Stored::class);
});

test('an order does not compete with itself', function () {
    $order = orderWithItem($this->item, 8, Committed::class);

    $this->actingAs($this->materialwart)
        ->put(route('orders.manage.update', $order), availabilityPayload([
            'event_start' => '2030-07-10',
            'event_end' => '2030-07-20',
            'items' => [$this->item->id => 10],
            'action' => 'save',
        ]))
        ->assertSessionHasNoErrors();

    expect($order->orderedItems()->sole()->quantity)->toBe(10);
});

test('a submitted order can not take material of another order when it is edited', function () {
    orderWithItem($this->item, 8, Committed::class);
    $order = orderWithItem($this->item, 2, Committed::class);

    $this->actingAs($this->materialwart)
        ->put(route('orders.manage.update', $order), availabilityPayload([
            'event_start' => '2030-07-10',
            'event_end' => '2030-07-20',
            'items' => [$this->item->id => 3],
            'action' => 'save',
        ]))
        ->assertSessionHasErrors('items');
});

test('a collection is not counted on top of the quantities the form already contains', function () {
    $collection = ItemCollection::factory()->create();
    $collection->items()->attach($this->item->id, ['quantity' => 6]);

    $this->actingAs($this->leader)
        ->post(route('orders.store'), availabilityPayload(['items' => [$this->item->id => 6], 'collections' => [$collection->id]]))
        ->assertSessionHasNoErrors();

    expect(Order::sole()->orderedItems()->sole()->quantity)->toBe(6);
});

test('a collection counts its quantities in units', function () {
    $lotItem = Item::factory()->create(['stock' => 10, 'lot' => 5]);
    $collection = ItemCollection::factory()->create();
    $collection->items()->attach($lotItem->id, ['quantity' => 10]);

    $this->actingAs($this->leader)
        ->post(route('orders.store'), availabilityPayload(['items' => [$lotItem->id => 2], 'collections' => [$collection->id]]))
        ->assertSessionHasNoErrors();
});

test('a collection can be part of an order several times', function () {
    $collection = ItemCollection::factory()->create();
    $collection->items()->attach($this->item->id, ['quantity' => 2]);

    $this->actingAs($this->leader)
        ->post(route('orders.store'), availabilityPayload(['items' => [$this->item->id => 4], 'collections' => [$collection->id, $collection->id]]))
        ->assertSessionHasNoErrors();

    expect(Order::sole()->itemCollections)->toHaveCount(2);
});

test('handing out records the quantities and undoing it clears them', function () {
    $order = orderWithItem($this->item, 4, Accepted::class);
    $service = app(OrderService::class);

    $service->transition($order, 'delivered');
    expect($order->orderedItems()->sole()->delivered)->toBe(4);

    $service->transition($order->fresh(), 'accepted');
    expect($order->orderedItems()->sole()->delivered)->toBe(0);
});

test('saving keeps what was recorded on existing positions', function () {
    $order = orderWithItem($this->item, 4, Committed::class);
    $position = $order->orderedItems()->sole();
    $position->update(['delivered' => 4, 'returned' => 2]);
    $otherItem = Item::factory()->create(['stock' => 10, 'lot' => 1]);

    $this->actingAs($this->materialwart)
        ->put(route('orders.manage.update', $order), availabilityPayload([
            'event_start' => '2030-07-10',
            'event_end' => '2030-07-20',
            'items' => [$this->item->id => 5, $otherItem->id => 1],
            'action' => 'save',
        ]))
        ->assertSessionHasNoErrors();

    expect($position->fresh())->quantity->toBe(5)->delivered->toBe(4)->returned->toBe(2)
        ->and($order->orderedItems()->count())->toBe(2);

    $this->actingAs($this->materialwart)
        ->put(route('orders.manage.update', $order), availabilityPayload([
            'event_start' => '2030-07-10',
            'event_end' => '2030-07-20',
            'items' => [$otherItem->id => 1],
            'action' => 'save',
        ]));

    expect($order->orderedItems()->sole()->item_id)->toBe($otherItem->id);
});

test('leaders see their own orders and the ones of their unit', function () {
    $otherUnit = Role::findByName('Pfadistufe');
    $colleagueOrder = Order::factory()->create(['user_id' => $this->materialwart->id, 'role_id' => $this->role->id, 'event' => 'Wolfsübung']);
    $foreignOrder = Order::factory()->create(['user_id' => $this->materialwart->id, 'role_id' => $otherUnit->id, 'event' => 'Pfadiübung']);

    $this->actingAs($this->leader)
        ->get(route('orders.index'))
        ->assertSee('Wolfsübung')
        ->assertDontSee('Pfadiübung');

    $this->actingAs($this->leader)->get(route('orders.show', $colleagueOrder))->assertSuccessful();
    $this->actingAs($this->leader)->get(route('orders.show', $foreignOrder))->assertForbidden();
    $this->actingAs($this->leader)->get(route('orders.manage.contents-pdf', $foreignOrder))->assertForbidden();
});
