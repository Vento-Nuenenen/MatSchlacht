<?php

use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\Order;
use App\Models\User;
use App\States\Order\Committed;
use App\States\Order\Stored;
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
    $this->item = Item::factory()->create(['item_group_id' => $this->itemGroup->id, 'stock' => 100]);
});

test('a user can create an order', function () {
    $orderData = [
        'event' => 'Summer Camp',
        'event_start' => now()->addDays(1)->format('Y-m-d'),
        'event_end' => now()->addDays(7)->format('Y-m-d'),
        'role_id' => $this->role->id,
        'user_id' => $this->user->id,
        'items' => [
            $this->item->id => 10,
        ],
        'action' => 'save',
    ];

    $response = $this->actingAs($this->user)
        ->post(route('orders.store'), $orderData);

    $response->assertRedirect(route('orders.index'));
    $this->assertDatabaseHas('orders', [
        'event' => 'Summer Camp',
        'user_id' => $this->user->id,
        'role_id' => $this->role->id,
    ]);

    $order = Order::where('event', 'Summer Camp')->first();
    $this->assertDatabaseHas('ordered_items', [
        'order_id' => $order->id,
        'item_id' => $this->item->id,
        'quantity' => 10,
    ]);
});

test('a user can update their own draft order', function () {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'role_id' => $this->role->id,
        'state' => Stored::class,
    ]);

    $updateData = [
        'event' => 'Updated Event',
        'event_start' => now()->addDays(2)->format('Y-m-d'),
        'event_end' => now()->addDays(8)->format('Y-m-d'),
        'role_id' => $this->role->id,
        'user_id' => $this->user->id,
        'items' => [
            $this->item->id => 5,
        ],
        'action' => 'save',
    ];

    $response = $this->actingAs($this->user)
        ->put(route('orders.update', $order), $updateData);

    $response->assertRedirect(route('orders.index'));
    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'event' => 'Updated Event',
    ]);
});

test('a user cannot update a committed order', function () {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'role_id' => $this->role->id,
        'state' => Committed::class,
    ]);

    $updateData = [
        'event' => 'Should Not Work',
        'event_start' => now()->addDays(2)->format('Y-m-d'),
        'event_end' => now()->addDays(8)->format('Y-m-d'),
        'role_id' => $this->role->id,
        'user_id' => $this->user->id,
    ];

    $response = $this->actingAs($this->user)
        ->put(route('orders.update', $order), $updateData);

    $response->assertForbidden();
});

test('a user can delete their own draft order', function () {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'role_id' => $this->role->id,
        'state' => Stored::class,
    ]);

    $response = $this->actingAs($this->user)
        ->delete(route('orders.destroy', $order));

    $response->assertRedirect(route('orders.index'));
    $this->assertDatabaseMissing('orders', ['id' => $order->id]);
});
