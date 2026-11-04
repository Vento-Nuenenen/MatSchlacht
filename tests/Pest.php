<?php

use App\Models\Item;
use App\Models\User;
use App\Services\ComponentManager;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

pest()->beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Mail::fake();
})->in('Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * A valid PNG image of one pixel, base64 encoded.
 */
const E2E_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

/**
 * Log in a new user for a browser test.
 *
 * @param  array<string, mixed>  $attributes
 */
function e2eLogin(string $role = 'Materialwart', array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole($role);
    test()->actingAs($user);

    return $user;
}

/**
 * Switch on app components, keeping the defaults of their settings.
 */
function e2eEnable(string ...$components): void
{
    $manager = app(ComponentManager::class);

    foreach ($components as $component) {
        $manager->update([$component => [
            'enabled' => true,
            'settings' => collect($manager->definitions()[$component]['settings'])->map(fn (array $setting) => $setting['default'])->all(),
        ]]);
    }
}

/**
 * @param  array<string, mixed>  $attributes
 */
function e2eItem(string $name, int $stock = 10, array $attributes = []): Item
{
    return Item::factory()->create(['name' => $name, 'stock' => $stock, 'lot' => 1, ...$attributes]);
}
