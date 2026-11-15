<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Materialwart');
});

test('the activation checkbox of the user form is stored', function (string $submitted, bool $isActive) {
    $user = User::factory()->create();

    $this->actingAs($this->admin)
        ->put(route('users.update', $user), [
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'email' => $user->email,
            'aktiv' => $submitted,
        ])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->aktiv)->toBe($isActive);
})->with([
    'ticked' => ['1', true],
    'unticked' => ['0', false],
]);

test('the user form submits a value for an unticked checkbox', function () {
    $this->actingAs($this->admin)
        ->get(route('users.create'))
        ->assertSee('<input type="hidden" name="aktiv" value="0">', false)
        ->assertSee('value="1"', false);
});

test('an inactive user cannot log in with a password', function () {
    $user = User::factory()->create(['aktiv' => false]);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);

    $this->get(route('overwatch'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Dein Konto ist deaktiviert.');

    $this->assertGuest();
});

test('a user is logged out as soon as the account is deactivated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('overwatch'))->assertSuccessful();

    $user->update(['aktiv' => false]);

    $this->get(route('overwatch'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('an active user stays logged in', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('overwatch'))
        ->assertSuccessful();

    $this->assertAuthenticated();
});
