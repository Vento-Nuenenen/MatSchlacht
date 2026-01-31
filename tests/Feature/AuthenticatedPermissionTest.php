<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthenticatedPermissionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_authenticated_user_has_authenticated_permission()
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/overwatch');

        $this->assertTrue($user->can('authenticated'));
        $this->assertTrue(Gate::allows('authenticated'));
    }

    /** @test */
    public function a_guest_does_not_have_authenticated_permission()
    {
        $this->assertFalse(Gate::allows('authenticated'));
    }
}
