<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('it shows all permissions of a user in the overwatch view', function () {
    $user = User::factory()->create();

    $role = Role::create(['name' => 'TestRole']);
    $permission1 = Permission::create(['name' => 'permission-one']);
    $permission2 = Permission::create(['name' => 'permission-two']);

    $role->givePermissionTo($permission1);
    $user->assignRole($role);
    $user->givePermissionTo($permission2);

    $response = $this->actingAs($user)->get('/overwatch');

    $response->assertStatus(200);
    $response->assertSee('permission-one');
    $response->assertSee('permission-two');
});
