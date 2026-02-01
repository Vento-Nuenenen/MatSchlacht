<?php

use App\Models\Order;
use App\Models\User;
use App\States\Order\Stored;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::findOrCreate('view orders');
    Permission::findOrCreate('edit orders');
    Permission::findOrCreate('delete orders');
    Permission::findOrCreate('edit own orders');
    Permission::findOrCreate('delete own orders');
});

test('user can edit own order with edit own orders permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view orders');
    $user->givePermissionTo('edit own orders');

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => Stored::class,
    ]);

    $response = $this->actingAs($user)->get(route('orders.edit', $order));

    $response->assertStatus(200);
});

test('edit button visible for own order with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view orders');
    $user->givePermissionTo('edit own orders');

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => Stored::class,
    ]);

    $response = $this->actingAs($user)->get(route('orders.index'));
    $response->assertSee(route('orders.edit', $order));
});

test('edit button not visible for others order with only own permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view orders');
    $user->givePermissionTo('edit own orders');

    $otherUser = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $otherUser->id,
        'status' => Stored::class,
    ]);

    $response = $this->actingAs($user)->get(route('orders.index'));
    $response->assertDontSee(route('orders.edit', $order));
});

test('user cannot edit others order with only edit own orders permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view orders');
    $user->givePermissionTo('edit own orders');

    $otherUser = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $otherUser->id,
        'status' => Stored::class,
    ]);

    $response = $this->actingAs($user)->get(route('orders.edit', $order));

    $response->assertStatus(403);
});

test('user can delete own order with delete own orders permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view orders');
    $user->givePermissionTo('delete own orders');

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => Stored::class,
    ]);

    $response = $this->actingAs($user)->delete(route('orders.destroy', $order));

    $response->assertRedirect(route('orders.index'));
    $this->assertDatabaseMissing('orders', ['id' => $order->id]);
});

test('user cannot delete others order with only delete own orders permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view orders');
    $user->givePermissionTo('delete own orders');

    $otherUser = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $otherUser->id,
        'status' => Stored::class,
    ]);

    $response = $this->actingAs($user)->delete(route('orders.destroy', $order));

    $response->assertStatus(403);
    $this->assertDatabaseHas('orders', ['id' => $order->id]);
});

test('user with general edit permission can edit any order', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('view orders');
    $user->givePermissionTo('edit orders');

    $otherUser = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $otherUser->id,
        'status' => Stored::class,
    ]);

    $response = $this->actingAs($user)->get(route('orders.edit', $order));

    $response->assertStatus(200);
});
