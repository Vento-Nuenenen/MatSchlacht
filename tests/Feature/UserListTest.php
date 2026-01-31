<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('user list shows roles', function () {
    $adminRole = Role::create(['name' => 'Materialwart']);
    $userRole = Role::create(['name' => 'Bezirk']);

    $admin = User::factory()->create();
    $admin->assignRole($adminRole);

    $otherUser = User::factory()->create(['scoutname' => 'TestScout']);
    $otherUser->assignRole($userRole);

    $response = $this->actingAs($admin)->get(route('users.index'));

    $response->assertStatus(200);
    $response->assertSee('TestScout');
    $response->assertSee('Bezirk');
    $response->assertSee('Materialwart');
});
