<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->wolfRole = Role::findByName('Wolfsstufe');
    $this->pfadiRole = Role::findByName('Pfadistufe');

    $this->wolfUser1 = User::factory()->create();
    $this->wolfUser1->assignRole($this->wolfRole);
    $this->wolfUser1->givePermissionTo(['view users', 'create users', 'edit users']);

    $this->wolfUser2 = User::factory()->create();
    $this->wolfUser2->assignRole($this->wolfRole);

    $this->pfadiUser = User::factory()->create();
    $this->pfadiUser->assignRole($this->pfadiRole);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Materialwart');
});

test('unprivileged user can only see users in their own role', function () {
    $response = $this->actingAs($this->wolfUser1)
        ->get(route('users.index'));

    $response->assertSuccessful();
    $response->assertSee($this->wolfUser2->name);
    $response->assertDontSee($this->pfadiUser->name);
});

test('privileged user can see all users', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('users.index'));

    $response->assertSuccessful();
    $response->assertSee($this->wolfUser2->name);
    $response->assertSee($this->pfadiUser->name);
});

test('unprivileged user can only see their own role in create/edit', function () {
    $response = $this->actingAs($this->wolfUser1)
        ->get(route('users.create'));

    $response->assertSuccessful();
    $response->assertSee($this->wolfRole->name);
    $response->assertDontSee($this->pfadiRole->name);
});

test('privileged user can see all roles in create/edit', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('users.create'));

    $response->assertSuccessful();
    $response->assertSee($this->wolfRole->name);
    $response->assertSee($this->pfadiRole->name);
});
