<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

function userPayload(User $user, array $overrides = []): array
{
    return [
        'firstname' => $user->firstname,
        'lastname' => $user->lastname,
        'email' => $user->email,
        ...$overrides,
    ];
}

test('unprivileged user can update users in their own role', function () {
    $this->actingAs($this->wolfUser1)
        ->put(route('users.update', $this->wolfUser2), userPayload($this->wolfUser2, ['firstname' => 'Neu', 'roles' => ['Wolfsstufe']]))
        ->assertRedirect(route('users.index'));

    expect($this->wolfUser2->fresh()->firstname)->toBe('Neu');
});

test('unprivileged user cannot update users outside their own role', function () {
    $originalEmail = $this->pfadiUser->email;

    $this->actingAs($this->wolfUser1)
        ->put(route('users.update', $this->pfadiUser), userPayload($this->pfadiUser, ['email' => 'neu@example.test']))
        ->assertForbidden();

    expect($this->pfadiUser->fresh()->email)->toBe($originalEmail);
});

test('unprivileged user cannot take over a privileged user that shares their role', function () {
    $this->admin->assignRole($this->wolfRole);
    $originalEmail = $this->admin->email;

    $this->actingAs($this->wolfUser1)
        ->put(route('users.update', $this->admin), userPayload($this->admin, ['email' => 'neu@example.test']))
        ->assertForbidden();

    expect($this->admin->fresh()->email)->toBe($originalEmail);
});

test('unprivileged user cannot send a password reset link outside their own role', function () {
    Notification::fake();

    $this->actingAs($this->wolfUser1)
        ->post(route('users.password-reset', $this->pfadiUser))
        ->assertForbidden();

    Notification::assertNothingSent();
});

test('unprivileged user cannot delete users outside their own role', function () {
    $this->wolfUser1->givePermissionTo('delete users');

    $this->actingAs($this->wolfUser1)
        ->delete(route('users.destroy', $this->pfadiUser))
        ->assertForbidden();

    $this->assertModelExists($this->pfadiUser);
});

test('updating a user keeps the roles the editor cannot manage', function () {
    $this->wolfUser2->assignRole($this->pfadiRole);

    $this->actingAs($this->wolfUser1)
        ->put(route('users.update', $this->wolfUser2), userPayload($this->wolfUser2, ['roles' => []]));

    expect($this->wolfUser2->fresh()->getRoleNames()->all())->toBe(['Pfadistufe']);
});
