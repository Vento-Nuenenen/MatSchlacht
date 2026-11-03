<?php

use App\Jobs\SendOrderStatusNotification;
use App\Mail\OrderStatusChanged;
use App\Models\Item;
use App\Models\ItemCollection;
use App\Models\ItemGroup;
use App\Models\Order;
use App\Models\User;
use App\Services\ComponentManager;
use App\States\Order\Accepted;
use App\States\Order\Committed;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->materialwart = User::factory()->create();
    $this->materialwart->assignRole('Materialwart');

    $this->role = Role::findByName('Wolfsstufe');
    $this->leader = User::factory()->create();
    $this->leader->assignRole($this->role);
});

/**
 * @param  array<string, mixed>  $settings
 */
function configureComponent(string $component, bool $enabled, array $settings = []): void
{
    app(ComponentManager::class)->update([$component => ['enabled' => $enabled, 'settings' => $settings]]);
}

/**
 * The full settings form payload with every component enabled and default settings.
 *
 * @return array<string, mixed>
 */
function componentPayload(array $overrides = []): array
{
    $components = [];

    foreach (app(ComponentManager::class)->definitions() as $component => $definition) {
        $components[$component] = [
            'enabled' => '1',
            'settings' => collect($definition['settings'])->map(fn (array $setting) => $setting['type'] === 'bool' ? '1' : $setting['default'])->all(),
        ];
    }

    return ['components' => array_replace_recursive($components, $overrides)];
}

test('the materialwart can see the component settings', function () {
    $this->actingAs($this->materialwart)
        ->get(route('settings.components.index'))
        ->assertSuccessful()
        ->assertSee('Kollektionen')
        ->assertSee('Mail-Benachrichtigungen');
});

test('users without the manage settings permission cannot see or change the settings', function () {
    $this->actingAs($this->leader)
        ->get(route('settings.components.index'))
        ->assertForbidden();

    $this->actingAs($this->leader)
        ->put(route('settings.components.update'), componentPayload(['item_collections' => ['enabled' => '0']]))
        ->assertForbidden();

    expect(app(ComponentManager::class)->isEnabled('item_collections'))->toBeTrue();
});

test('the materialwart can disable a component and configure its settings', function () {
    $this->actingAs($this->materialwart)
        ->put(route('settings.components.update'), componentPayload([
            'item_collections' => ['enabled' => '0'],
            'order_notifications' => ['settings' => [
                'notify_on_created' => '0',
                'material_responsibles' => ' lager@example.com ,chef@example.com ',
            ]],
        ]))
        ->assertRedirect(route('settings.components.index'))
        ->assertSessionHasNoErrors();

    $components = app(ComponentManager::class);
    $components->flush();

    expect($components->isEnabled('item_collections'))->toBeFalse()
        ->and($components->isEnabled('item_contents'))->toBeTrue()
        ->and($components->setting('order_notifications', 'notify_on_created'))->toBeFalse()
        ->and($components->settingList('order_notifications', 'material_responsibles'))->toBe(['lager@example.com', 'chef@example.com']);
});

test('at least one login method has to stay enabled', function () {
    $this->actingAs($this->materialwart)
        ->put(route('settings.components.update'), componentPayload([
            'password_login' => ['enabled' => '0'],
            'azure_login' => ['enabled' => '0'],
        ]))
        ->assertSessionHasErrors('components.password_login.enabled');

    expect(app(ComponentManager::class)->isEnabled('password_login'))->toBeTrue();
});

test('the material responsibles have to be valid e-mail addresses', function () {
    $this->actingAs($this->materialwart)
        ->put(route('settings.components.update'), componentPayload([
            'order_notifications' => ['settings' => ['material_responsibles' => 'lager@example.com, kein-mail']],
        ]))
        ->assertSessionHasErrors('components.order_notifications.settings.material_responsibles');
});

test('routes of a disabled component respond with not found', function (string $component, string $method, Closure $url) {
    configureComponent($component, false);

    $this->actingAs($this->materialwart)
        ->{$method}($url())
        ->assertNotFound();
})->with([
    'collections' => ['item_collections', 'get', fn () => route('item-collections.index')],
    'contents' => ['item_contents', 'post', fn () => route('items.contents.store', Item::factory()->create())],
    'packing list' => ['item_contents', 'get', fn () => route('orders.manage.contents-pdf', Order::factory()->create())],
    'azure group mappings' => ['azure_login', 'get', fn () => route('azure-group-role-mappings.index')],
    'azure login' => ['azure_login', 'get', fn () => route('azure.login')],
]);

test('the password login is rejected when it is disabled', function () {
    configureComponent('password_login', false);

    $this->post(route('login'), ['email' => $this->leader->email, 'password' => 'password'])
        ->assertNotFound();

    $this->assertGuest();
});

test('the login page only shows the enabled login methods', function () {
    configureComponent('azure_login', true, ['button_label' => 'Mit Pfadi-Konto anmelden', 'sync_group_roles' => true]);
    configureComponent('password_login', false);

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('Mit Pfadi-Konto anmelden')
        ->assertDontSee('name="password"', false);

    configureComponent('password_login', true);
    configureComponent('azure_login', false);

    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('name="password"', false)
        ->assertDontSee(route('azure.login', absolute: false));
});

test('the navigation hides links of disabled components', function () {
    configureComponent('item_collections', false);

    $this->actingAs($this->materialwart)
        ->get(route('overwatch'))
        ->assertSuccessful()
        ->assertDontSee(route('item-collections.index'))
        ->assertSee(route('settings.components.index'));
});

test('collections are neither offered nor linked when the component is disabled', function () {
    Mail::fake();
    $item = Item::factory()->create(['item_group_id' => ItemGroup::factory()->create()->id, 'stock' => 10, 'lot' => 1]);
    $collection = ItemCollection::create(['name' => 'Kochset']);
    configureComponent('item_collections', false);

    $this->actingAs($this->leader)
        ->get(route('orders.create'))
        ->assertSuccessful()
        ->assertDontSee('Kochset');

    $this->actingAs($this->leader)->post(route('orders.store'), [
        'event' => 'Herbstlager',
        'event_start' => now()->addDays(3)->format('Y-m-d'),
        'event_end' => now()->addDays(5)->format('Y-m-d'),
        'role_id' => $this->role->id,
        'user_id' => $this->leader->id,
        'items' => [$item->id => 1],
        'collections' => [$collection->id],
        'action' => 'save',
    ]);

    expect(Order::sole()->itemCollections)->toBeEmpty();
});

test('uploads are ignored when attachments are disabled', function () {
    Storage::fake('attachments');
    configureComponent('attachments', false);
    $group = ItemGroup::factory()->create();

    $this->actingAs($this->materialwart)
        ->post(route('items.store'), [
            'name' => 'Zelt',
            'stock' => 10,
            'lot' => 1,
            'item_group_id' => $group->id,
            'files' => [UploadedFile::fake()->image('zelt.jpg')],
        ])
        ->assertRedirect(route('items.index'));

    expect(Item::where('name', 'Zelt')->sole()->attachments)->toBeEmpty();
});

test('no order created mail is sent when the notification is turned off', function (bool $isComponentEnabled, bool $notifyOnCreated) {
    Mail::fake();
    configureComponent('order_notifications', $isComponentEnabled, ['notify_on_created' => $notifyOnCreated, 'notify_on_status_change' => true]);

    Order::factory()->create(['user_id' => $this->leader->id, 'role_id' => $this->role->id]);

    Mail::assertNothingQueued();
})->with([
    'component disabled' => [false, true],
    'option disabled' => [true, false],
]);

test('no status mail is dispatched when status notifications are turned off', function () {
    Queue::fake();
    configureComponent('order_notifications', true, ['notify_on_created' => true, 'notify_on_status_change' => false]);
    $order = Order::factory()->create(['user_id' => $this->leader->id, 'role_id' => $this->role->id, 'state' => Committed::class]);

    $this->actingAs($this->materialwart)
        ->post(route('orders.manage.transition', $order), ['to' => 'accepted']);

    expect($order->fresh()->state)->toBeInstanceOf(Accepted::class);
    Queue::assertNotPushed(SendOrderStatusNotification::class);
});

test('the status mail goes to the material responsibles from the settings', function () {
    Mail::fake();
    config(['mail.material_responsibles' => 'env@example.com']);
    configureComponent('order_notifications', true, [
        'notify_on_created' => true,
        'notify_on_status_change' => true,
        'material_responsibles' => 'lager@example.com',
    ]);
    $order = Order::factory()->create(['user_id' => $this->leader->id, 'role_id' => $this->role->id, 'state' => Accepted::class]);

    (new SendOrderStatusNotification($order, 'accepted'))->handle();

    Mail::assertSent(OrderStatusChanged::class, fn (OrderStatusChanged $mail) => $mail->hasTo('lager@example.com')
        && ! $mail->hasTo('env@example.com'));
});
