<?php

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\ItemGroup;
use App\Models\Order;
use App\Models\User;
use App\Services\ComponentManager;
use App\States\Order\Committed;
use App\States\Order\Stored;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Mail::fake();
    $this->seed(RolesAndPermissionsSeeder::class);
    app(ComponentManager::class)->update(['appearance' => ['settings' => [
        'primary_color' => '#b1138b',
        'per_page_phone' => 5,
        'per_page_tablet' => 6,
        'per_page_desktop' => 5,
    ]]]);

    $this->materialwart = User::factory()->create(['lastname' => 'AAA']);
    $this->materialwart->assignRole('Materialwart');
    $this->actingAs($this->materialwart);
});

test('lists are split into pages', function (Closure $createEntries, string $routeName, string $first, string $last) {
    $createEntries();

    $this->get(route($routeName))
        ->assertSuccessful()
        ->assertSee($first)
        ->assertDontSee($last)
        ->assertSee('von 7');

    $this->get(route($routeName, ['page' => 2]))
        ->assertSuccessful()
        ->assertSee($last)
        ->assertDontSee($first);
})->with([
    'items' => [function () {
        $group = ItemGroup::factory()->create();
        foreach (range(1, 7) as $number) {
            Item::factory()->create(['item_group_id' => $group->id, 'name' => "Material-{$number}x"]);
        }
    }, 'items.index', 'Material-1x', 'Material-7x'],
    'collections' => [function () {
        foreach (range(1, 7) as $number) {
            ItemCollection::factory()->create(['name' => "Kollektion-{$number}x"]);
        }
    }, 'item-collections.index', 'Kollektion-1x', 'Kollektion-7x'],
    'orders' => [function () {
        foreach (range(1, 7) as $number) {
            Order::factory()->create(['event' => "Anlass-{$number}x", 'event_start' => "2030-01-0{$number}", 'event_end' => '2030-02-01']);
        }
    }, 'orders.index', 'Anlass-1x', 'Anlass-7x'],
]);

test('users are listed by name across the pages', function () {
    foreach (range(1, 5) as $number) {
        User::factory()->create(['lastname' => "Name{$number}", 'scoutname' => null]);
    }

    $this->get(route('users.index'))->assertSee('Name1')->assertDontSee('Name5');
    $this->get(route('users.index', ['page' => 2]))->assertSee('Name5')->assertDontSee('Name1');
});

test('the links keep the filter of the list', function () {
    $group = ItemGroup::factory()->create();
    Item::factory()->count(7)->create(['item_group_id' => $group->id]);

    $this->get(route('items.index', ['item_group_id' => $group->id]))
        ->assertSee("item_group_id={$group->id}&amp;page=2", false);
});

test('a short list has no pagination', function () {
    Item::factory()->count(4)->create();

    $this->get(route('items.index'))->assertSuccessful()->assertDontSee('data-per-page', false);
});

test('the default length of a list depends on the screen size', function (?string $screen, int $expectedEntries) {
    Item::factory()->count(12)->create();

    $request = $screen === null ? $this : $this->withUnencryptedCookie(Controller::SCREEN_COOKIE, $screen);

    expect($request->get(route('items.index'))->viewData('items')->perPage())->toBe($expectedEntries);
})->with([
    'phone' => ['phone', 5],
    'tablet' => ['tablet', 6],
    'desktop' => ['desktop', 5],
    'unknown value' => ['fernseher', 5],
    'no information' => [null, 5],
]);

test('the choice of the device wins over the screen size', function () {
    Item::factory()->count(12)->create();

    $response = $this->withUnencryptedCookies([Controller::SCREEN_COOKIE => 'phone', Controller::PER_PAGE_COOKIE => '10'])
        ->get(route('items.index'));

    expect($response->viewData('items')->perPage())->toBe(10);
    $response->assertSee('<option value="10" selected>10</option>', false);
});

test('only the offered lengths can be chosen', function (string $value) {
    Item::factory()->count(12)->create();

    $response = $this->withUnencryptedCookie(Controller::PER_PAGE_COOKIE, $value)->get(route('items.index'));

    expect($response->viewData('items')->perPage())->toBe(5);
})->with(['1000000', '0', '-5', 'alle']);

test('the length can still be changed when everything fits on one page', function () {
    Item::factory()->count(12)->create();

    $this->withUnencryptedCookie(Controller::PER_PAGE_COOKIE, '100')
        ->get(route('items.index'))
        ->assertSee('data-per-page', false)
        ->assertDontSee('aria-label="Nächste Seite"', false);
});

test('the layout tells the server the screen size', function () {
    $this->get(route('overwatch'))->assertSee('matschlacht_screen=', false);
});

test('the sections of the order management have their own pages', function () {
    $role = Role::findByName('Wolfsstufe');

    foreach (range(1, 7) as $number) {
        Order::factory()->create(['state' => Stored::class, 'role_id' => $role->id, 'event' => "Entwurf-{$number}x", 'event_start' => "2030-01-0{$number}", 'event_end' => '2030-02-01']);
        Order::factory()->create(['state' => Committed::class, 'role_id' => $role->id, 'event' => "Eingereicht-{$number}x", 'event_start' => "2030-01-0{$number}", 'event_end' => '2030-02-01']);
    }

    $this->get(route('orders.manage', ['committed_page' => 2]))
        ->assertSuccessful()
        ->assertSee('Entwurf-1x')
        ->assertDontSee('Eingereicht-1x')
        ->assertSee('Eingereicht-7x');
});

test('the page size has to stay in a sensible range', function () {
    $components = [];

    foreach (app(ComponentManager::class)->definitions() as $component => $definition) {
        $components[$component] = [
            'enabled' => '1',
            'settings' => collect($definition['settings'])
                ->map(fn (array $setting) => $setting['type'] === 'bool' ? '1' : ($setting['type'] === 'secret' ? '' : $setting['default']))
                ->all(),
        ];
    }

    $components['appearance']['settings']['per_page_phone'] = 1000;

    $this->put(route('settings.components.update'), ['components' => $components])
        ->assertSessionHasErrors('components.appearance.settings.per_page_phone');
});
