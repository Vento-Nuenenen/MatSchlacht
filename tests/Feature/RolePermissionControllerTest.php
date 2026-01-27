<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $materialwart;
    protected $permission;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user with 'manage roles' permission
        $this->user = User::factory()->create();
        $role_manage_permission = Permission::create(['name' => 'manage roles']);
        $this->user->givePermissionTo($role_manage_permission);

        // Create Materialwart role
        $this->materialwart = Role::create(['name' => 'Materialwart']);
        $this->permission = Permission::create(['name' => 'view users']);
    }

    /** @test */
    public function it_cannot_edit_materialwart_role()
    {
        $response = $this->actingAs($this->user)
            ->get(route('roles.edit', $this->materialwart->id));

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('error', 'Die Rolle Materialwart kann nicht bearbeitet werden.');
    }

    /** @test */
    public function it_cannot_update_materialwart_role()
    {
        $response = $this->actingAs($this->user)
            ->put(route('roles.update', $this->materialwart->id), [
                'role_name' => 'Materialwart Updated',
                'permissions' => [$this->permission->name]
            ]);

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('error', 'Die Rolle Materialwart kann nicht bearbeitet werden.');

        $this->assertEquals('Materialwart', $this->materialwart->refresh()->name);
        $this->assertFalse($this->materialwart->hasPermissionTo($this->permission->name));
    }

    /** @test */
    public function it_can_update_other_roles()
    {
        $other_role = Role::create(['name' => 'Other Role']);

        $response = $this->actingAs($this->user)
            ->put(route('roles.update', $other_role->id), [
                'role_name' => 'Other Role Updated',
                'permissions' => [$this->permission->name]
            ]);

        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('success', 'Rolle erfolgreich aktualisiert!');
        $this->assertEquals('Other Role Updated', $other_role->refresh()->name);
        $this->assertTrue($other_role->hasPermissionTo($this->permission->name));
    }

    /** @test */
    public function it_hides_edit_button_for_materialwart_in_index()
    {
        $response = $this->actingAs($this->user)
            ->get(route('roles.index'));

        $response->assertStatus(200);
        $response->assertDontSee(route('roles.edit', $this->materialwart->id));

        $other_role = Role::create(['name' => 'Other Role']);
        $response = $this->actingAs($this->user)
            ->get(route('roles.index'));
        $response->assertSee(route('roles.edit', $other_role->id));
    }
}
