<?php

use App\Models\User;
use App\Models\AzureGroupRoleMapping;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed permissions
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->user = User::factory()->create();
    $this->user->assignRole('Materialwart');
});

test('unauthorized user cannot access mappings', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('azure-group-role-mappings.index'));
    $response->assertStatus(403);
});

test('can list mappings', function () {
    AzureGroupRoleMapping::create([
        'azure_group_id' => 'group-123',
        'role_name' => 'Materialwart'
    ]);

    $response = $this->actingAs($this->user)->get(route('azure-group-role-mappings.index'));

    $response->assertStatus(200);
    $response->assertSee('group-123');
    $response->assertSee('Materialwart');
});

test('can create mapping', function () {
    $response = $this->actingAs($this->user)->post(route('azure-group-role-mappings.store'), [
        'azure_group_id' => 'new-group-456',
        'role_name' => 'Materialwart'
    ]);

    $response->assertRedirect(route('azure-group-role-mappings.index'));
    $this->assertDatabaseHas('azure_group_role_mappings', [
        'azure_group_id' => 'new-group-456',
        'role_name' => 'Materialwart'
    ]);
});

test('can update mapping', function () {
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
});

test('can delete mapping', function () {
    $mapping = AzureGroupRoleMapping::create([
        'azure_group_id' => 'to-delete',
        'role_name' => 'Materialwart'
    ]);

    $response = $this->actingAs($this->user)->delete(route('azure-group-role-mappings.destroy', $mapping->id));

    $response->assertRedirect(route('azure-group-role-mappings.index'));
    $this->assertDatabaseMissing('azure_group_role_mappings', [
        'id' => $mapping->id
    ]);
});
