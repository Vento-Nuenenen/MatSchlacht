<?php

use App\Models\Order;
use App\Models\User;
use App\States\Order\Accepted;
use App\States\Order\Archived;
use App\States\Order\Committed;
use App\States\Order\Delivered;
use App\States\Order\Returned;
use App\States\Order\Stored;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('order starts in stored state', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'TestRole']);

    $order = Order::create([
        'event' => 'Test Event',
        'event_start' => now(),
        'event_end' => now()->addDay(),
        'user_id' => $user->id,
        'role_id' => $role->id,
        // 'status' is not provided, should default to Stored
    ]);

    $this->assertInstanceOf(Stored::class, $order->status);
    $this->assertEquals('stored', (string) $order->status);
});

test('order transitions through all states', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'TestRole']);

    $order = Order::create([
        'event' => 'Test Event',
        'event_start' => now(),
        'event_end' => now()->addDay(),
        'user_id' => $user->id,
        'role_id' => $role->id,
    ]);

    $this->assertInstanceOf(Stored::class, $order->status);

    $order->status->transitionTo(Committed::class);
    $this->assertInstanceOf(Committed::class, $order->fresh()->status);

    $order->status->transitionTo(Accepted::class);
    $this->assertInstanceOf(Accepted::class, $order->fresh()->status);

    $order->status->transitionTo(Delivered::class);
    $this->assertInstanceOf(Delivered::class, $order->fresh()->status);

    $order->status->transitionTo(Returned::class);
    $this->assertInstanceOf(Returned::class, $order->fresh()->status);

    $order->status->transitionTo(Archived::class);
    $this->assertInstanceOf(Archived::class, $order->fresh()->status);
});

test('can archive from committed state', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'TestRole']);

    $order = Order::create([
        'event' => 'Test Event',
        'event_start' => now(),
        'event_end' => now()->addDay(),
        'user_id' => $user->id,
        'role_id' => $role->id,
    ]);

    $order->status->transitionTo(Committed::class);
    $order->status->transitionTo(Archived::class);
    $this->assertInstanceOf(Archived::class, $order->fresh()->status);
});

test('invalid transition fails', function () {
    $user = User::factory()->create();
    $role = Role::create(['name' => 'TestRole']);

    $order = Order::create([
        'event' => 'Test Event',
        'event_start' => now(),
        'event_end' => now()->addDay(),
        'user_id' => $user->id,
        'role_id' => $role->id,
    ]);

    $this->expectException(\Spatie\ModelStates\Exceptions\TransitionNotFound::class);
    $order->status->transitionTo(Accepted::class); // Stored to Accepted is not allowed
});
