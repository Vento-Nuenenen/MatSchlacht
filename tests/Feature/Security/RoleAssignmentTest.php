<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('a user with view and edit users permission but not Materialwart cannot assign Materialwart role to others', function () {
    $adminRole = Role::where('name', 'Materialwart')->first();
    $alRole = Role::where('name', 'Abteilungsleitung')->first();

    // User with Abteilungsleitung role (has 'view users' and 'edit users'?)
    // Wait, let me check RolesAndPermissionsSeeder again for 'edit users'
    $user = User::factory()->create();
    $user->assignRole($alRole);

    // Abteilungsleitung only has 'view users' in seeder.
    // Let's give them 'edit users' too for this test.
    $user->givePermissionTo('edit users');

    $otherUser = User::factory()->create();

    $response = $this->actingAs($user)->put(route('users.update', $otherUser), [
        'firstname' => 'Test',
        'lastname' => 'User',
        'email' => 'test@example.com',
        'roles' => ['Materialwart'],
    ]);

    // It should either fail validation or the role should not be assigned
    $otherUser->refresh();
    expect($otherUser->hasRole('Materialwart'))->toBeFalse();
});

test('a user with view and edit users permission but not Materialwart cannot assign Materialwart role to themselves', function () {
    $alRole = Role::where('name', 'Abteilungsleitung')->first();

    $user = User::factory()->create();
    $user->assignRole($alRole);
    $user->givePermissionTo('edit users');

    $response = $this->actingAs($user)->put(route('users.update', $user), [
        'firstname' => $user->firstname,
        'lastname' => $user->lastname,
        'email' => $user->email,
        'roles' => ['Materialwart', 'Abteilungsleitung'],
    ]);

    $user->refresh();
    expect($user->hasRole('Materialwart'))->toBeFalse();
});
