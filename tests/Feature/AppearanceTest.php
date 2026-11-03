<?php

use App\Models\User;
use App\Services\ComponentManager;
use App\Services\NavigationService;
use App\Services\ThemeService;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->materialwart = User::factory()->create(['firstname' => 'Maria', 'lastname' => 'Muster']);
    $this->materialwart->assignRole('Materialwart');

    $this->leader = User::factory()->create();
    $this->leader->assignRole('Wolfsstufe');
});

test('the layout uses the default primary colour', function () {
    $this->actingAs($this->leader)
        ->get(route('overwatch'))
        ->assertSuccessful()
        ->assertSee('--app-primary:#b1138b', false)
        ->assertSee('<meta name="theme-color" content="#b1138b">', false);
});

test('the materialwart can change the primary colour', function () {
    $components = [];

    foreach (app(ComponentManager::class)->definitions() as $component => $definition) {
        $components[$component] = [
            'enabled' => '1',
            'settings' => collect($definition['settings'])
                ->map(fn (array $setting) => $setting['type'] === 'bool' ? '1' : ($setting['type'] === 'secret' ? '' : $setting['default']))
                ->all(),
        ];
    }

    $components['appearance']['settings']['primary_color'] = '#1F6F43';

    $this->actingAs($this->materialwart)
        ->put(route('settings.components.update'), ['components' => $components])
        ->assertSessionHasNoErrors();

    $this->actingAs($this->leader)
        ->get(route('overwatch'))
        ->assertSee('--app-primary:#1f6f43', false)
        ->assertSee('--bs-primary-rgb:31, 111, 67', false);

    $components['appearance']['settings']['primary_color'] = 'violett';

    $this->actingAs($this->materialwart)
        ->put(route('settings.components.update'), ['components' => $components])
        ->assertSessionHasErrors('components.appearance.settings.primary_color');
});

test('text on the primary colour stays readable', function (string $color, string $expectedContrast) {
    app(ComponentManager::class)->update(['appearance' => ['settings' => ['primary_color' => $color]]]);

    expect(app(ThemeService::class)->cssVariables())->toContain("--app-primary-contrast:{$expectedContrast}");
})->with([
    'dark colour' => ['#b1138b', '#ffffff'],
    'light colour' => ['#f5d76e', '#1f1a24'],
]);

test('the navigation only lists what the user may use', function () {
    $this->actingAs($this->leader)
        ->get(route('overwatch'))
        ->assertSee(route('orders.index'))
        ->assertDontSee(route('orders.manage'))
        ->assertDontSee(route('users.index'))
        ->assertDontSee(route('settings.components.index'));

    $this->actingAs($this->materialwart)
        ->get(route('overwatch'))
        ->assertSee(route('orders.manage'))
        ->assertSee(route('users.index'))
        ->assertSee(route('settings.components.index'));
});

test('the bottom tab bar shows the four most important entries the user may use', function () {
    $navigation = app(NavigationService::class);

    expect(collect($navigation->mobileTabs($this->materialwart))->pluck('route')->all())
        ->toBe(['overwatch', 'orders.index', 'orders.manage', 'items.index'])
        ->and(collect($navigation->mobileTabs($this->leader))->pluck('route')->all())
        ->toBe(['overwatch', 'orders.index', 'items.index']);
});

test('the avatar shows the initials of the user', function () {
    expect($this->materialwart->initials())->toBe('MM');

    $this->actingAs($this->materialwart)
        ->get(route('overwatch'))
        ->assertSee('<span class="app-avatar">MM</span>', false);
});
