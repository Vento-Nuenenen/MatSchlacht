<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create a user with 'manage roles' permission
    $this->user = User::factory()->create();
    $this->materialwart = Role::findOrCreate('Materialwart');
    $this->user->assignRole($this->materialwart);
    Permission::findOrCreate('manage roles');
    $this->permission = Permission::findOrCreate('view users');
});

test('it cannot edit materialwart role', function () {
    $response = $this->actingAs($this->user)
        ->get(route('roles.edit', $this->materialwart->id));

    $response->assertRedirect(route('roles.index'));
    $response->assertSessionHas('error', 'Die Rolle Materialwart kann nicht bearbeitet werden.');
});

test('it cannot update materialwart role', function () {
    $response = $this->actingAs($this->user)
        ->put(route('roles.update', $this->materialwart->id), [
            'name' => 'Materialwart Updated',
            'permissions' => [$this->permission->name]
        ]);

    $response->assertRedirect(route('roles.index'));
    $response->assertSessionHas('error', 'Die Rolle Materialwart kann nicht bearbeitet werden.');

    $this->assertEquals('Materialwart', $this->materialwart->refresh()->name);
    $this->assertFalse($this->materialwart->hasPermissionTo($this->permission->name));
});

test('it can update other roles', function () {
    $other_role = Role::create(['name' => 'Other Role']);

    $response = $this->actingAs($this->user)
        ->put(route('roles.update', $other_role->id), [
            'name' => 'Other Role Updated',
            'permissions' => [$this->permission->name]
        ]);

    $response->assertRedirect(route('roles.index'));
    $response->assertSessionHas('success', 'Rolle erfolgreich aktualisiert!');
    $this->assertEquals('Other Role Updated', $other_role->refresh()->name);
    $this->assertTrue($other_role->hasPermissionTo($this->permission->name));
});

test('it hides edit button for materialwart in index', function () {
    // Add manage roles permission to the user role
    $this->user->assignRole($this->materialwart);

    $response = $this->actingAs($this->user)
        ->get(route('roles.index'));

    $response->assertStatus(200);
    $response->assertDontSee(route('roles.edit', $this->materialwart->id));

    $other_role = Role::create(['name' => 'Other Role']);
    $response = $this->actingAs($this->user)
        ->get(route('roles.index'));
    $response->assertSee(route('roles.edit', $other_role->id));
});

test('it shows permissions grouped by module in edit view', function () {
    $other_role = Role::create(['name' => 'Test Role']);
    Permission::findOrCreate('create items');
    Permission::findOrCreate('edit items');
    Permission::findOrCreate('view orders');
    Permission::findOrCreate('manage azure mappings');

    $response = $this->actingAs($this->user)
        ->get(route('roles.edit', $other_role->id));

    $response->assertStatus(200);
    $response->assertSee('Items');
    $response->assertSee('Orders');
    $response->assertSee('Azure');
});
