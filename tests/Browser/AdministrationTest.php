<?php

use App\Models\HitobitoAccessRule;
use App\Models\Order;
use App\Models\User;
use App\Services\ComponentManager;
use App\Services\FileStorage\FileStorageManager;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

/**
 * Saves the settings form. The file storage needs to be complete for that.
 */
function e2eSaveSettings(mixed $page): mixed
{
    return $page->select('setting-file_storage-provider', 'local')
        ->press('form[action$="/settings/components"] .justify-content-end button[type=submit]');
}

beforeEach(function () {
    $this->admin = e2eLogin();
});

test('a component is switched off and disappears from the app', function () {
    $page = visit('/settings/components')->uncheck('component-item_collections');

    e2eSaveSettings($page)
        ->assertSee('Einstellungen wurden gespeichert')
        ->assertNotPresent('.app-sidebar a[href$="/item-collections"]');

    expect(app(ComponentManager::class)->isEnabled('item_collections'))->toBeFalse();
});

test('both login methods cannot be switched off', function () {
    $page = visit('/settings/components')->uncheck('component-password_login')->uncheck('component-azure_login');

    e2eSaveSettings($page)->assertSee('Mindestens eine Login-Methode');
});

test('a new primary colour is applied at once', function () {
    $page = visit('/settings/components')->fill('setting-appearance-primary_color', '#1f6f43');

    e2eSaveSettings($page)
        ->assertScript("getComputedStyle(document.documentElement).getPropertyValue('--app-primary').trim()", '#1f6f43')
        ->assertScript("getComputedStyle(document.querySelector('.btn-primary')).backgroundColor", 'rgb(31, 111, 67)');
});

test('the fields of the chosen storage provider are shown', function () {
    visit('/settings/components')
        ->select('setting-file_storage-provider', 'sftp')
        ->assertVisible('#setting-file_storage-sftp_host')
        ->assertMissing('#setting-file_storage-s3_bucket')
        ->select('setting-file_storage-provider', 'nextcloud')
        ->assertVisible('#setting-file_storage-nextcloud_url')
        ->assertMissing('#setting-file_storage-sftp_host');
});

test('a password of the file storage is never shown again', function () {
    $page = visit('/settings/components')
        ->select('setting-file_storage-provider', 'sftp')
        ->fill('setting-file_storage-sftp_host', 'files.example.com')
        ->fill('setting-file_storage-sftp_username', 'mat')
        ->fill('setting-file_storage-sftp_password', 'streng-geheim')
        ->press('form[action$="/settings/components"] .justify-content-end button[type=submit]')
        ->assertSee('Einstellungen wurden gespeichert');

    $page->assertValue('#setting-file_storage-sftp_password', '')
        ->assertSourceMissing('streng-geheim');
});

test('the connection to the file storage can be tested', function () {
    app(ComponentManager::class)->updateSetting('file_storage', 'provider', 'local');
    app(FileStorageManager::class)->forgetDisks();
    Storage::fake('attachments_local');

    visit('/settings/components')
        ->click('button[form="file-storage-test-form"]')
        ->assertSee('funktioniert');
});

test('a user is created and can be deleted again', function () {
    visit('/users/create')
        ->fill('firstname', 'Lea')
        ->fill('lastname', 'Muster')
        ->fill('email', 'lea@example.com')
        ->press('form[action$="/users"] button[type=submit]')
        ->assertPathIs('/users')
        ->assertSee('lea@example.com');

    $user = User::where('email', 'lea@example.com')->sole();

    $page = visit('/users');
    $page->script('window.confirm = () => true');
    $page->click("form[action$=\"/users/{$user->id}\"] button[type=submit]")
        ->assertDontSee('lea@example.com');
});

test('a user with orders is not deleted', function () {
    $user = User::factory()->create();
    Order::factory()->create(['user_id' => $user->id]);

    $page = visit('/users');
    $page->script('window.confirm = () => true');
    $page->click("form[action$=\"/users/{$user->id}\"] button[type=submit]")
        ->assertSee('hat Bestellungen');

    expect(User::find($user->id))->not->toBeNull();
});

test('a role is created with permissions', function () {
    visit('/roles/create')
        ->fill('name', 'Rover-Team')
        ->press('form[action$="/roles"] button[type=submit]')
        ->assertPathIs('/roles')
        ->assertSee('Rover-Team')
        ->assertNoJavaScriptErrors();

    expect(Role::where('name', 'Rover-Team')->exists())->toBeTrue();
});

test('a hitobito rule can give a role without access', function () {
    e2eEnable('hitobito_login');

    visit('/hitobito-access-rules/create')
        ->fill('hitobito_group_id', '7')
        ->uncheck('grants_access')
        ->select('role_name', 'Wolfsstufe')
        ->press('form[action$="/hitobito-access-rules"] button[type=submit]')
        ->assertPathIs('/hitobito-access-rules')
        ->assertSee('Nur Rolle');

    expect(HitobitoAccessRule::sole()->grants_access)->toBeFalse();
});
