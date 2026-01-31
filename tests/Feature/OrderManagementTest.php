<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('order management page shows all state titles even when empty', function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('Materialwart');

    $response = $this->actingAs($user)->get(route('orders.manage'));

    $response->assertStatus(200);

    // Check for all state display names
    $response->assertSee('Bestellt'); // Committed
    $response->assertSee('Bestätigt'); // Accepted
    $response->assertSee('Ausgegeben'); // Delivered (I should check this one too)
    $response->assertSee('Zurückgegeben'); // Returned
    $response->assertSee('Entwurf'); // Stored
    $response->assertSee('Archiviert'); // Archived

    // Check for the "empty" messages
    $response->assertSee('Keine Bestellungen in diesem Status.');
});
