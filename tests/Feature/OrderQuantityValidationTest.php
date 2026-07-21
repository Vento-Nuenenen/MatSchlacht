<?php

use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\Order;
use App\Models\User;
use App\States\Order\Delivered;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->role = Role::findByName('Wolfsstufe');
    $this->user = User::factory()->create();
    $this->user->assignRole($this->role);

    // Give user admin permissions to use management controller
    $this->user->givePermissionTo('manage orders');

    $this->itemGroup = ItemGroup::factory()->create();
    $this->item = Item::factory()->create([
        'item_group_id' => $this->itemGroup->id,
        'stock' => 100,
        'name' => 'Test Item',
    ]);
});

test('it prevents returning more items than delivered', function () {
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'role_id' => $this->role->id,
        'state' => Delivered::class,
    ]);

    $order->orderedItems()->create([
        'item_id' => $this->item->id,
        'quantity' => 10,
        'confirmed' => true,
        'delivered' => true,
        'returned' => 0,
        'repair' => 0,
        'broken' => 0,
    ]);

    $updateData = [
        'event' => $order->event,
        'event_start' => $order->event_start->format('Y-m-d'),
        'event_end' => $order->event_end->format('Y-m-d'),
        'role_id' => $order->role_id,
        'user_id' => $order->user_id,
        'returned' => [
            $this->item->id => 11, // More than delivered (10)
        ],
    ];

    $response = $this->actingAs($this->user)
        ->put(route('orders.manage.update', $order), $updateData);

    $response->assertSessionHasErrors(['returned.'.$this->item->id]);
});

test('it prevents entering excessively high quantities', function () {
    $orderData = [
        'event' => 'Huge Quantity Event',
        'event_start' => now()->addDays(1)->format('Y-m-d'),
        'event_end' => now()->addDays(7)->format('Y-m-d'),
        'role_id' => $this->role->id,
        'user_id' => $this->user->id,
        'items' => [
            $this->item->id => 1000000, // 1 million
        ],
        'action' => 'save',
    ];

    $response = $this->actingAs($this->user)
        ->post(route('orders.store'), $orderData);

    $response->assertSessionHasErrors(['items.'.$this->item->id]);
});
