<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AuthenticatedPermissionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function an_authenticated_user_has_authenticated_permission()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/overwatch');

        $this->assertTrue($user->can('authenticated'));
        $this->assertTrue(Gate::allows('authenticated'));
    }

    /** @test */
    public function an_authenticated_user_has_authenticated_role()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/overwatch');

        $this->assertTrue($user->fresh()->hasRole('authenticated'), 'User should have the "authenticated" role.');
    }

    /** @test */
    public function a_guest_does_not_have_authenticated_permission()
    {
        $this->assertFalse(Gate::allows('authenticated'));
    }

    /** @test */
    public function authenticated_permission_and_role_exists_in_seeder()
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->assertDatabaseHas('permissions', ['name' => 'authenticated']);
        $this->assertDatabaseHas('roles', ['name' => 'authenticated']);
    }
}
