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
            $this->item->id => 6, // Total 11 (5 + 6)
        ],
        'collections' => [
            $collection->id,
        ],
        'action' => 'order',
    ];

    $response = $this->actingAs($this->user)
        ->post(route('orders.store'), $orderData);

    // This might be tricky because we need to decide where the error should be attached
    $response->assertSessionHasErrors();
});
