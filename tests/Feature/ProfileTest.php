<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_accessible()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertSee('Profil');
        $response->assertSee($user->email);
    }

    public function test_password_can_be_updated()
    {
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
    }

    public function test_password_update_requires_correct_current_password()
    {
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
    }

    public function test_password_update_requires_confirmation()
    {
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
    }

    public function test_profile_page_shows_azure_user_status()
    {
        $user = User::factory()->create(['azure_id' => 'azure-123']);

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertSee('Azure Benutzer');
        $response->assertSee('Da du mit deinem Azure-Konto angemeldet bist, kannst du dein Passwort hier nicht ändern.');
        $response->assertDontSee('Passwort ändern');
    }

    public function test_azure_user_cannot_update_password()
    {
        $user = User::factory()->create(['azure_id' => 'azure-123', 'password' => Hash::make('some-password')]);

        $response = $this->actingAs($user)->put(route('profile.password.update'), [
            'current_password' => 'some-password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Azure-Benutzer können ihr Passwort hier nicht ändern.');
        $this->assertTrue(Hash::check('some-password', $user->refresh()->password));
    }
}
