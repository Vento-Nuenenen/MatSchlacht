<?php

use App\Jobs\SendOrderStatusNotification;
use App\Mail\OrderStatusChanged;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\ItemGroup;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    config(['app.available_locales' => ['de' => 'Deutsch', 'fr' => 'Français']]);
    app('translator')->addLines(['*.Passwort' => 'Mot de passe', '*.Profil' => 'Mon profil'], 'fr');
});

it('uses the language of the account before the one of the browser', function () {
    $user = User::factory()->create(['locale' => 'fr']);

    $this->actingAs($user)
        ->withHeader('Accept-Language', 'de')
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Mon profil')
        ->assertSee('<html lang="fr"', false);
});

it('uses the language of the browser without a language on the account', function () {
    $this->withHeader('Accept-Language', 'fr-CH,fr;q=0.9')
        ->get(route('login'))
        ->assertOk()
        ->assertSee('Mot de passe');

    expect(app()->getLocale())->toBe('fr');
});

it('falls back to the default language', function (?string $accountLocale, string $browserLocale) {
    $user = User::factory()->create(['locale' => $accountLocale]);

    $this->actingAs($user)
        ->withHeader('Accept-Language', $browserLocale)
        ->get(route('profile.edit'))
        ->assertOk();

    expect(app()->getLocale())->toBe('de');
})->with([
    'language the app does not offer' => [null, 'es'],
    'language that was removed from the app' => ['es', 'es'],
]);

it('stores the chosen language on the account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->put(route('profile.locale.update'), ['locale' => 'fr'])
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->locale)->toBe('fr');
});

it('rejects a language the app does not offer', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put(route('profile.locale.update'), ['locale' => 'es'])
        ->assertSessionHasErrors('locale');

    expect($user->refresh()->locale)->toBeNull();
});

it('offers the language switch only with several languages', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('profile.edit'))->assertSee(route('profile.locale.update'));

    config(['app.available_locales' => ['de' => 'Deutsch']]);

    $this->actingAs($user)->get(route('profile.edit'))->assertDontSee(route('profile.locale.update'));
});

it('shows validation messages in German', function () {
    config(['app.available_locales' => ['de' => 'Deutsch']]);

    $this->post(route('login'), ['email' => 'keine-adresse', 'password' => ''])
        ->assertSessionHasErrors(['password' => 'Passwort muss ausgefüllt sein.']);
});

it('sends the order status mail in the language of the orderer', function () {
    Mail::fake();

    $order = Order::factory()->create(['user_id' => User::factory()->create(['locale' => 'fr'])->id]);

    (new SendOrderStatusNotification($order, 'committed'))->handle();

    Mail::assertSent(OrderStatusChanged::class, fn (OrderStatusChanged $mail) => $mail->locale === 'fr');
});

it('lists missing translations and adds them on request', function () {
    $languagePath = sys_get_temp_dir().'/lang-check-'.uniqid();
    File::ensureDirectoryExists($languagePath);
    app()->useLangPath($languagePath);

    $this->artisan('lang:check', ['locale' => 'fr', '--write' => true])
        ->expectsOutputToContain('Passwort')
        ->expectsOutputToContain(config('components.storage.label'))
        ->assertFailed();

    $texts = json_decode(File::get("{$languagePath}/fr.json"), true);

    expect($texts)->toHaveKey('Passwort', '');

    File::put("{$languagePath}/fr.json", json_encode(array_map(fn () => 'Traduit', $texts)));

    $this->artisan('lang:check', ['locale' => 'fr'])->assertSuccessful();

    File::deleteDirectory($languagePath);
});

it('opens a collection with material for editing', function () {
    $materialwart = User::factory()->create();
    $materialwart->assignRole('Materialwart');

    Item::factory()->create(['item_group_id' => ItemGroup::factory()->create()->id]);

    $this->actingAs($materialwart)
        ->get(route('item-collections.edit', ItemCollection::factory()->create()))
        ->assertOk();
});
