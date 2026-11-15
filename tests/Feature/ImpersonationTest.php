<?php

use App\Http\Controllers\ImpersonationController;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('Materialwart');

    $this->user = User::factory()->create(['firstname' => 'Lea', 'lastname' => 'Leiterin']);
    $this->user->assignRole('Wolfsstufe');
});

test('a materialwart can sign in as another user', function () {
    $this->actingAs($this->admin)
        ->post(route('users.impersonate', $this->user))
        ->assertRedirect(route('overwatch'))
        ->assertSessionHas(ImpersonationController::SESSION_KEY, $this->admin->id);

    $this->assertAuthenticatedAs($this->user);

    $this->get(route('overwatch'))
        ->assertSuccessful()
        ->assertSee('Testmodus:')
        ->assertSee('Zurück zu meinem Konto');
});

test('the impersonated user only gets their own permissions', function () {
    $this->actingAs($this->admin)->post(route('users.impersonate', $this->user));

    $this->get(route('users.index'))->assertForbidden();
});

test('leaving the impersonation restores the own account', function () {
    $this->actingAs($this->admin)->post(route('users.impersonate', $this->user));

    $this->post(route('impersonation.leave'))
        ->assertRedirect(route('users.index'))
        ->assertSessionMissing(ImpersonationController::SESSION_KEY);

    $this->assertAuthenticatedAs($this->admin);

    $this->get(route('overwatch'))->assertDontSee('Testmodus:');
});

test('leaving is not possible without an impersonation', function () {
    $this->actingAs($this->user)
        ->post(route('impersonation.leave'))
        ->assertForbidden();

    $this->assertAuthenticatedAs($this->user);
});

test('impersonation is refused', function (Closure $actor, Closure $target) {
    $actor = $actor->call($this);
    $target = $target->call($this);

    $this->actingAs($actor)
        ->post(route('users.impersonate', $target))
        ->assertForbidden();

    $this->assertAuthenticatedAs($actor);
})->with([
    'by a non privileged user' => [
        function () {
            $leader = User::factory()->create();
            $leader->assignRole(['Abteilungsleitung', 'Wolfsstufe']);

            return $leader;
        },
        fn () => $this->user,
    ],
    'for a privileged user' => [
        fn () => $this->admin,
        function () {
            $other = User::factory()->create();
            $other->assignRole('Materialwart');

            return $other;
        },
    ],
    'for an inactive user' => [
        fn () => $this->admin,
        fn () => User::factory()->create(['aktiv' => false]),
    ],
    'for oneself' => [
        fn () => $this->admin,
        fn () => $this->admin,
    ],
]);

test('the password cannot be changed while impersonating', function () {
    $this->actingAs($this->admin)->post(route('users.impersonate', $this->user));

    $passwordHash = $this->user->fresh()->password;

    $this->from(route('profile.edit'))
        ->put(route('profile.password.update'), [
            'current_password' => 'password',
            'password' => 'new-secret-password',
            'password_confirmation' => 'new-secret-password',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('error', 'Im Testmodus nicht möglich.');

    expect($this->user->fresh()->password)->toBe($passwordHash);
});

test('the user list offers impersonation only where it is allowed', function () {
    $this->actingAs($this->admin)
        ->get(route('users.index'))
        ->assertSee(route('users.impersonate', $this->user))
        ->assertDontSee(route('users.impersonate', $this->admin));
});
