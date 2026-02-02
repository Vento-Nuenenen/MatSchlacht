<?php

use App\Models\User;
use App\Models\Order;
use App\Models\Item;
use App\States\Order\Committed;
use App\States\Order\Stored;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('overwatch dashboard shows user stats', function () {
    $user = User::factory()->create(['firstname' => 'John', 'scoutname' => null]);

    // Create some orders for the user
    Order::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->get('/overwatch');

    $response->assertStatus(200);
    $response->assertSee('Dashboard');
    $response->assertSee('Willkommen zurück, John!');
    $response->assertSee('Meine Bestellungen');
    $response->assertSee('3'); // Total orders count
});

test('overwatch dashboard shows admin stats', function () {
    $adminRole = Role::create(['name' => 'Materialwart']);
    $admin = User::factory()->create(['firstname' => 'AdminUser']);
    $admin->assignRole($adminRole);

    User::factory()->count(5)->create();
    Item::factory()->count(10)->create();
    Order::factory()->count(2)->create(['status' => Committed::class]);
    Order::factory()->count(1)->create(['status' => Stored::class]);

    $response = $this->actingAs($admin)->get('/overwatch');

    $response->assertStatus(200);
    $response->assertSee('Verwaltung');
    $response->assertSee('Offene Bestellungen');
    $response->assertSee('2'); // Pending orders
    $response->assertSee('10'); // Total items
    $response->assertSee('6'); // Total users (5 + 1 admin)
});
