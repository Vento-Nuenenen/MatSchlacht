<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('a person logs in with a password and logs out again', function () {
    User::factory()->create(['email' => 'lea@example.com', 'password' => 'geheim1234', 'firstname' => 'Lea', 'scoutname' => 'Pfiff']);

    visit('/login')
        ->fill('email', 'lea@example.com')
        ->fill('password', 'geheim1234')
        ->press('button[type=submit]')
        ->assertPathIs('/overwatch')
        ->assertSee('Hallo Pfiff')
        ->click('.app-user-button')
        ->click('.app-user-menu button[type=submit]')
        ->assertPathIs('/login')
        ->assertNoJavaScriptErrors();
});

test('a wrong password is refused on the login page', function () {
    User::factory()->create(['email' => 'lea@example.com', 'password' => 'geheim1234']);

    visit('/login')
        ->fill('email', 'lea@example.com')
        ->fill('password', 'falsch')
        ->press('button[type=submit]')
        ->assertPathIs('/login')
        ->assertPresent('.is-invalid')
        ->assertNoJavaScriptErrors();
});

test('guests are sent to the login page', function () {
    visit('/items')->assertPathIs('/login');
});

test('the password can be changed in the profile', function () {
    $user = e2eLogin('Wolfsstufe', ['password' => 'altes-passwort']);

    visit('/profile')
        ->fill('current_password', 'altes-passwort')
        ->fill('password', 'neues-passwort-1')
        ->fill('password_confirmation', 'neues-passwort-1')
        ->press('form[action$="profile/password"] button[type=submit]')
        ->assertNoJavaScriptErrors();

    expect(Hash::check('neues-passwort-1', $user->fresh()->password))->toBeTrue();
});
