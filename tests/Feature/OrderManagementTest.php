<?php

namespace Tests\Feature;

use App\Models\User;
use App\States\Order\Committed;
use App\States\Order\Accepted;
use App\States\Order\Delivered;
use App\States\Order\Returned;
use App\States\Order\Stored;
use App\States\Order\Archived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_management_page_shows_all_state_titles_even_when_empty()
    {
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
    }
}
