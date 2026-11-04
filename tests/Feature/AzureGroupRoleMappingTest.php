<?php

use App\Models\AzureGroupRoleMapping;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->materialwart = User::factory()->create();
    $this->materialwart->assignRole('Materialwart');

    $this->mapping = AzureGroupRoleMapping::create(['azure_group_id' => 'group-a', 'role_name' => 'Wolfsstufe']);
});

test('a mapping can be saved with its own group id', function () {
    $this->actingAs($this->materialwart)
        ->put(route('azure-group-role-mappings.update', $this->mapping), ['azure_group_id' => 'group-a', 'role_name' => 'Pfadistufe'])
        ->assertSessionHasNoErrors();

    expect($this->mapping->fresh()->role_name)->toBe('Pfadistufe');
});

test('a mapping cannot take over the group id of another mapping', function () {
    AzureGroupRoleMapping::create(['azure_group_id' => 'group-b', 'role_name' => 'Biberstufe']);

    $this->actingAs($this->materialwart)
        ->put(route('azure-group-role-mappings.update', $this->mapping), ['azure_group_id' => 'group-b', 'role_name' => 'Wolfsstufe'])
        ->assertSessionHasErrors('azure_group_id');

    expect($this->mapping->fresh()->azure_group_id)->toBe('group-a');
});
