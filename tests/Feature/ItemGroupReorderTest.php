<?php

use App\Models\ItemGroup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Materialwart');

    $this->user = User::factory()->create();
    $this->user->assignRole('Wolfsstufe');
});

test('admin can reorder item groups', function () {
    $group1 = ItemGroup::factory()->create(['position' => 1]);
    $group2 = ItemGroup::factory()->create(['position' => 2]);

    $response = $this->actingAs($this->admin)
        ->post(route('item-groups.reorder'), [
            'positions' => [$group2->id, $group1->id],
        ]);

    $response->assertSuccessful();
    $response->assertJson(['success' => true]);

    expect($group1->refresh()->position)->toBe(2);
    expect($group2->refresh()->position)->toBe(1);
});

test('regular user cannot reorder item groups', function () {
    $group1 = ItemGroup::factory()->create(['position' => 1]);
    $group2 = ItemGroup::factory()->create(['position' => 2]);

    $response = $this->actingAs($this->user)
        ->post(route('item-groups.reorder'), [
            'positions' => [$group2->id, $group1->id],
        ]);

    $response->assertForbidden();
});
