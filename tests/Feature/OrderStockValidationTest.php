<?php

use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\ItemGroup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->role = Role::findByName('Wolfsstufe');
    $this->user = User::factory()->create();
    $this->user->assignRole($this->role);

    $this->itemGroup = ItemGroup::factory()->create();
    $this->item = Item::factory()->create([
        'item_group_id' => $this->itemGroup->id,
        'stock' => 10,
        'name' => 'Test Item',
    ]);
});

test('it prevents ordering more items than available in stock', function () {
    $orderData = [
        'event' => 'Overstock Event',
        'event_start' => now()->addDays(1)->format('Y-m-d'),
        'event_end' => now()->addDays(7)->format('Y-m-d'),
        'role_id' => $this->role->id,
        'user_id' => $this->user->id,
        'items' => [
            $this->item->id => 11, // More than stock (10)
        ],
        'action' => 'order',
    ];

    $response = $this->actingAs($this->user)
        ->post(route('orders.store'), $orderData);

    $response->assertSessionHasErrors(['items']);
});

test('it prevents ordering more items than available in stock via collections', function () {
    $collection = ItemCollection::factory()->create(['name' => 'Test Collection']);
    $collection->items()->attach($this->item->id, ['quantity' => 11]);

    $orderData = [
        'event' => 'Overstock Collection Event',
        'event_start' => now()->addDays(1)->format('Y-m-d'),
        'event_end' => now()->addDays(7)->format('Y-m-d'),
        'role_id' => $this->role->id,
        'user_id' => $this->user->id,
        'collections' => [
            $collection->id,
        ],
        'action' => 'order',
    ];

    $response = $this->actingAs($this->user)
        ->post(route('orders.store'), $orderData);

    $response->assertSessionHasErrors(['collections']);
});

test('it prevents ordering more items than available in stock via mixed items and collections', function () {
    $collection = ItemCollection::factory()->create(['name' => 'Test Collection']);
    $collection->items()->attach($this->item->id, ['quantity' => 5]);

    $orderData = [
        'event' => 'Overstock Mixed Event',
        'event_start' => now()->addDays(1)->format('Y-m-d'),
        'event_end' => now()->addDays(7)->format('Y-m-d'),
        'role_id' => $this->role->id,
        'user_id' => $this->user->id,
        'items' => [
            $this->item->id => 11, // The form submits the collection's 5 plus 6 more
        ],
        'collections' => [
            $collection->id,
        ],
        'action' => 'order',
    ];

    $response = $this->actingAs($this->user)
        ->post(route('orders.store'), $orderData);

    $response->assertSessionHasErrors('collections');
});

test('it checks the stock of a draft order per item', function () {
    $response = $this->actingAs($this->user)->post(route('orders.store'), [
        'event' => 'Draft Overstock Event',
        'event_start' => now()->addDays(1)->format('Y-m-d'),
        'event_end' => now()->addDays(7)->format('Y-m-d'),
        'role_id' => $this->role->id,
        'user_id' => $this->user->id,
        'items' => [$this->item->id => 11],
        'action' => 'save',
    ]);

    $response->assertSessionHasErrors(['items.'.$this->item->id]);
});

test('it counts ordered lots as units when checking the stock', function () {
    $lotItem = Item::factory()->create([
        'item_group_id' => $this->itemGroup->id,
        'stock' => 10,
        'lot' => 5,
    ]);

    $response = $this->actingAs($this->user)->post(route('orders.store'), [
        'event' => 'Lot Event',
        'event_start' => now()->addDays(1)->format('Y-m-d'),
        'event_end' => now()->addDays(7)->format('Y-m-d'),
        'role_id' => $this->role->id,
        'user_id' => $this->user->id,
        'items' => [$lotItem->id => 3],
        'action' => 'order',
    ]);

    $response->assertSessionHasErrors(['items']);
});

test('it reports an overstocked item only once when ordering', function () {
    $collection = ItemCollection::factory()->create(['name' => 'Test Collection']);
    $collection->items()->attach($this->item->id, ['quantity' => 5]);

    $response = $this->actingAs($this->user)->post(route('orders.store'), [
        'event' => 'Single Error Event',
        'event_start' => now()->addDays(1)->format('Y-m-d'),
        'event_end' => now()->addDays(7)->format('Y-m-d'),
        'role_id' => $this->role->id,
        'user_id' => $this->user->id,
        'items' => [$this->item->id => 11],
        'collections' => [$collection->id],
        'action' => 'order',
    ]);

    expect(collect($response->getSession()->get('errors')['default']['messages'])->flatten())->toHaveCount(1);
});

test('it accepts an order within the available stock', function () {
    $response = $this->actingAs($this->user)->post(route('orders.store'), [
        'event' => 'Fitting Event',
        'event_start' => now()->addDays(1)->format('Y-m-d'),
        'event_end' => now()->addDays(7)->format('Y-m-d'),
        'role_id' => $this->role->id,
        'user_id' => $this->user->id,
        'items' => [$this->item->id => 10],
        'action' => 'order',
    ]);

    $response->assertSessionHasNoErrors()->assertRedirect(route('orders.index'));
});
