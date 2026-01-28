<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\AzureGroupRoleMapping;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AzureGroupRoleMappingTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('Materialwart');
    }

    public function test_unauthorized_user_cannot_access_mappings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('azure-group-role-mappings.index'));
        $response->assertStatus(403);
    }

    public function test_can_list_mappings(): void
    {
        AzureGroupRoleMapping::create([
            'azure_group_id' => 'group-123',
            'role_name' => 'Materialwart'
        ]);

        $response = $this->actingAs($this->user)->get(route('azure-group-role-mappings.index'));

        $response->assertStatus(200);
        $response->assertSee('group-123');
        $response->assertSee('Materialwart');
    }

    public function test_can_create_mapping(): void
    {
        $response = $this->actingAs($this->user)->post(route('azure-group-role-mappings.store'), [
            'azure_group_id' => 'new-group-456',
            'role_name' => 'Materialwart'
        ]);

        $response->assertRedirect(route('azure-group-role-mappings.index'));
        $this->assertDatabaseHas('azure_group_role_mappings', [
            'azure_group_id' => 'new-group-456',
            'role_name' => 'Materialwart'
        ]);
    }

    public function test_can_update_mapping(): void
    {
        $mapping = AzureGroupRoleMapping::create([
            'azure_group_id' => 'old-group',
            'role_name' => 'Materialwart'
        ]);

        $response = $this->actingAs($this->user)->put(route('azure-group-role-mappings.update', $mapping->id), [
            'azure_group_id' => 'updated-group',
            'role_name' => 'Materialwart'
        ]);

        $response->assertRedirect(route('azure-group-role-mappings.index'));
        $this->assertDatabaseHas('azure_group_role_mappings', [
            'id' => $mapping->id,
            'azure_group_id' => 'updated-group'
        ]);
    }

    public function test_can_delete_mapping(): void
    {
        $mapping = AzureGroupRoleMapping::create([
            'azure_group_id' => 'to-delete',
            'role_name' => 'Materialwart'
        ]);

        $response = $this->actingAs($this->user)->delete(route('azure-group-role-mappings.destroy', $mapping->id));

        $response->assertRedirect(route('azure-group-role-mappings.index'));
        $this->assertDatabaseMissing('azure_group_role_mappings', [
            'id' => $mapping->id
        ]);
    }
}
