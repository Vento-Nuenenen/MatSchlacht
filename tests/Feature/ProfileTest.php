<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('profile page is accessible', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertStatus(200);
    $response->assertSee('Profil');
    $response->assertSee($user->email);
});

test('password can be updated', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->actingAs($user)->put(route('profile.password.update'), [
        'current_password' => 'old-password',
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Passwort erfolgreich aktualisiert.');

    $this->assertTrue(Hash::check('new-password123', $user->refresh()->password));
});

test('password update requires correct current_password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->actingAs($user)->put(route('profile.password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ]);

    $response->assertSessionHasErrors('current_password');
    $this->assertTrue(Hash::check('old-password', $user->refresh()->password));
});

test('password update requires confirmation', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $response = $this->actingAs($user)->put(route('profile.password.update'), [
        'current_password' => 'old-password',
        'password' => 'new-password123',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertTrue(Hash::check('old-password', $user->refresh()->password));
});

test('profile page shows azure user status', function () {
    $user = User::factory()->create(['azure_id' => 'azure-123']);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertStatus(200);
    $response->assertSee('Azure Benutzer');
    $response->assertSee('Da du mit einem Azure-Konto angemeldet bist, kannst du dein Passwort hier nicht ändern.');
    $response->assertDontSee('Passwort ändern');
});

test('azure user cannot update password', function () {
    $user = User::factory()->create(['azure_id' => 'azure-123', 'password' => Hash::make('some-password')]);

    $response = $this->actingAs($user)->put(route('profile.password.update'), [
        'current_password' => 'some-password',
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Azure-Benutzer können ihr Passwort hier nicht ändern.');
    $this->assertTrue(Hash::check('some-password', $user->refresh()->password));
});
