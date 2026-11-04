<?php

use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\Order;
use App\Models\User;
use App\States\Order\Accepted;
use App\States\Order\Archived;
use App\States\Order\Committed;
use App\States\Order\Delivered;
use App\States\Order\Stored;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->role = Role::findByName('Wolfsstufe');
    $this->orderer = User::factory()->create();
    $this->orderer->assignRole($this->role);

    $this->materialwart = User::factory()->create();
    $this->materialwart->assignRole('Materialwart');

    $this->item = Item::factory()->create([
        'item_group_id' => ItemGroup::factory()->create()->id,
        'stock' => 100,
        'lot' => 1,
    ]);
});

function managedOrder(string $state, array $attributes = []): Order
{
    return Order::factory()->create(array_merge([
        'user_id' => test()->orderer->id,
        'role_id' => test()->role->id,
        'state' => $state,
    ], $attributes));
}

test('the overwatch page is reachable for any user', function () {
    $this->actingAs(User::factory()->create())
        ->get('/overwatch')
        ->assertSuccessful();
});

test('the management index lists active orders but hides archived ones', function () {
    managedOrder(Committed::class, ['event' => 'Pfingstlager']);
    managedOrder(Archived::class, ['event' => 'Altes Lager']);

    $this->actingAs($this->materialwart)
        ->get(route('orders.manage'))
        ->assertSuccessful()
        ->assertSee('Pfingstlager')
        ->assertDontSee('Altes Lager');
});

test('users without order edit permission cannot open the management index', function () {
    $this->actingAs($this->orderer)
        ->get(route('orders.manage'))
        ->assertForbidden();
});

test('the management edit page renders an order', function () {
    $order = managedOrder(Committed::class, ['event' => 'Sommerlager']);

    $this->actingAs($this->materialwart)
        ->get(route('orders.manage.edit', $order))
        ->assertSuccessful()
        ->assertSee('Sommerlager');
});

test('a privileged user viewing an order is redirected to the management page', function () {
    $order = managedOrder(Committed::class);

    $this->actingAs($this->materialwart)
        ->get(route('orders.show', $order))
        ->assertRedirect(route('orders.manage.edit', $order));
});

test('a manager can perform an allowed state transition', function () {
    $order = managedOrder(Committed::class);

    $this->actingAs($this->materialwart)
        ->post(route('orders.manage.transition', $order), ['to' => 'accepted'])
        ->assertSessionHas('success');

    expect($order->fresh()->state)->toBeInstanceOf(Accepted::class);
});

test('a manager cannot perform a disallowed state transition', function () {
    $order = managedOrder(Stored::class);

    $this->actingAs($this->materialwart)
        ->post(route('orders.manage.transition', $order), ['to' => 'delivered'])
        ->assertSessionHas('error');

    expect($order->fresh()->state)->toBeInstanceOf(Stored::class);
});

test('a manager updating a delivered order stores returned quantities in units', function () {
    $lotItem = Item::factory()->create([
        'item_group_id' => $this->item->item_group_id,
        'stock' => 100,
        'lot' => 5,
    ]);
    $order = managedOrder(Delivered::class);
    $order->orderedItems()->create([
        'item_id' => $lotItem->id, 'quantity' => 20,
        'delivered' => true, 'returned' => 0, 'repair' => 0, 'broken' => 0,
    ]);

    $this->actingAs($this->materialwart)
        ->put(route('orders.manage.update', $order), [
            'event' => 'Nach dem Lager',
            'event_start' => $order->event_start->format('Y-m-d'),
            'event_end' => $order->event_end->format('Y-m-d'),
            'role_id' => $order->role_id,
            'user_id' => $order->user_id,
            'returned' => [$lotItem->id => 3],
        ])
        ->assertRedirect(route('orders.manage'));

    assertDatabaseHas('orders', ['id' => $order->id, 'event' => 'Nach dem Lager']);
    assertDatabaseHas('ordered_items', ['order_id' => $order->id, 'item_id' => $lotItem->id, 'returned' => 15]);
    expect($order->fresh()->state)->toBeInstanceOf(Delivered::class);
});

test('a manager updating an accepted order replaces its items and keeps its state', function () {
    $otherItem = Item::factory()->create(['item_group_id' => $this->item->item_group_id, 'stock' => 100, 'lot' => 1]);
    $order = managedOrder(Accepted::class);
    $order->orderedItems()->create([
        'item_id' => $this->item->id, 'quantity' => 4,
        'delivered' => false, 'returned' => 0, 'repair' => 0, 'broken' => 0,
    ]);

    $this->actingAs($this->materialwart)
        ->put(route('orders.manage.update', $order), [
            'event' => $order->event,
            'event_start' => $order->event_start->format('Y-m-d'),
            'event_end' => $order->event_end->format('Y-m-d'),
            'role_id' => $order->role_id,
            'user_id' => $order->user_id,
            'items' => [$otherItem->id => 7, $this->item->id => 0],
        ])
        ->assertRedirect(route('orders.manage'));

    assertDatabaseMissing('ordered_items', ['order_id' => $order->id, 'item_id' => $this->item->id]);
    assertDatabaseHas('ordered_items', ['order_id' => $order->id, 'item_id' => $otherItem->id, 'quantity' => 7]);
    expect($order->fresh()->state)->toBeInstanceOf(Accepted::class);
});

test('a non-privileged manager is redirected back to the order after updating', function () {
    $leader = User::factory()->create();
    $leader->assignRole(['Abteilungsleitung', $this->role]);
    $order = managedOrder(Committed::class);

    $this->actingAs($leader)
        ->put(route('orders.manage.update', $order), [
            'event' => $order->event,
            'event_start' => $order->event_start->format('Y-m-d'),
            'event_end' => $order->event_end->format('Y-m-d'),
            'role_id' => $order->role_id,
            'user_id' => $order->user_id,
        ])
        ->assertRedirect(route('orders.manage.edit', $order));
});

test('the order sheet pdf can only be exported for accepted orders', function (string $state, bool $isExportable) {
    $order = managedOrder($state);
    $order->orderedItems()->create([
        'item_id' => $this->item->id, 'quantity' => 2,
        'delivered' => false, 'returned' => 0, 'repair' => 0, 'broken' => 0,
    ]);

    $response = $this->actingAs($this->materialwart)
        ->from(route('orders.manage.edit', $order))
        ->get(route('orders.manage.order-pdf', $order));

    if ($isExportable) {
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        expect($response->getContent())->toStartWith('%PDF');
    } else {
        $response->assertRedirect(route('orders.manage.edit', $order))->assertSessionHas('error');
    }
})->with([
    'accepted' => [Accepted::class, true],
    'committed' => [Committed::class, false],
]);

test('the return sheet pdf can only be exported for delivered orders', function (string $state, bool $isExportable) {
    $order = managedOrder($state);
    $order->orderedItems()->create([
        'item_id' => $this->item->id, 'quantity' => 2,
        'delivered' => true, 'returned' => 0, 'repair' => 0, 'broken' => 0,
    ]);

    $response = $this->actingAs($this->materialwart)
        ->from(route('orders.manage.edit', $order))
        ->get(route('orders.manage.return-pdf', $order));

    if ($isExportable) {
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        expect($response->getContent())->toStartWith('%PDF');
    } else {
        $response->assertRedirect(route('orders.manage.edit', $order))->assertSessionHas('error');
    }
})->with([
    'delivered' => [Delivered::class, true],
    'accepted' => [Accepted::class, false],
]);

test('the packing list pdf can be exported with and without item contents', function (bool $hasContents) {
    $order = managedOrder(Accepted::class);
    $order->orderedItems()->create([
        'item_id' => $this->item->id, 'quantity' => 2,
        'delivered' => false, 'returned' => 0, 'repair' => 0, 'broken' => 0,
    ]);

    if ($hasContents) {
        $this->item->contents()->create(['name' => 'Hering', 'quantity' => 12]);
    }

    $response = $this->actingAs($this->materialwart)
        ->get(route('orders.manage.contents-pdf', $order));

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toStartWith('%PDF');
})->with([
    'with contents' => [true],
    'without contents' => [false],
]);

test('only users allowed to edit orders can export the order sheet', function () {
    $order = managedOrder(Accepted::class);

    $this->actingAs($this->orderer)
        ->get(route('orders.manage.order-pdf', $order))
        ->assertForbidden();
});
