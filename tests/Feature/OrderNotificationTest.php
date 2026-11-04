<?php

use App\Jobs\SendOrderStatusNotification;
use App\Mail\OrderCreated;
use App\Mail\OrderStatusChanged;
use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\Order;
use App\Models\User;
use App\States\Order\Accepted;
use App\States\Order\Committed;
use App\States\Order\Delivered;
use App\States\Order\Stored;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->role = Role::findByName('Wolfsstufe');
    $this->orderer = User::factory()->create();
    $this->orderer->assignRole($this->role);

    $this->materialwart = User::factory()->create();
    $this->materialwart->assignRole('Materialwart');

    $this->item = Item::factory()->create([
        'item_group_id' => ItemGroup::factory()->create()->id,
        'stock' => 100,
        'lot' => 1,
    ]);
});

function orderPayload(array $overrides = []): array
{
    return array_merge([
        'event' => 'Herbstlager',
        'event_start' => now()->addDays(3)->format('Y-m-d'),
        'event_end' => now()->addDays(5)->format('Y-m-d'),
        'role_id' => test()->role->id,
        'user_id' => test()->orderer->id,
        'items' => [test()->item->id => 2],
        'action' => 'save',
    ], $overrides);
}

test('creating an order queues an order created mail for every member of the ordering unit', function () {
    Mail::fake();
    $colleague = User::factory()->create();
    $colleague->assignRole($this->role);

    $this->actingAs($this->orderer)->post(route('orders.store'), orderPayload());

    Mail::assertQueued(OrderCreated::class, 2);
    Mail::assertQueued(OrderCreated::class, fn (OrderCreated $mail) => $mail->hasTo($colleague->email));
});

test('creating an order directly as committed stores it as committed', function () {
    Mail::fake();

    $this->actingAs($this->orderer)->post(route('orders.store'), orderPayload(['action' => 'order']));

    expect(Order::firstWhere('event', 'Herbstlager')->state)->toBeInstanceOf(Committed::class);
});

test('committing a draft order dispatches exactly one status notification', function () {
    Queue::fake();
    $order = Order::factory()->create([
        'user_id' => $this->orderer->id,
        'role_id' => $this->role->id,
        'state' => Stored::class,
    ]);

    $this->actingAs($this->orderer)
        ->put(route('orders.update', $order), orderPayload(['action' => 'order']))
        ->assertRedirect(route('orders.index'));

    expect($order->fresh()->state)->toBeInstanceOf(Committed::class);
    Queue::assertPushed(SendOrderStatusNotification::class, 1);
    Queue::assertPushed(SendOrderStatusNotification::class, fn ($job) => $job->statusName === 'committed');
});

test('saving a draft order again does not dispatch a status notification', function () {
    Queue::fake();
    $order = Order::factory()->create([
        'user_id' => $this->orderer->id,
        'role_id' => $this->role->id,
        'state' => Stored::class,
    ]);

    $this->actingAs($this->orderer)->put(route('orders.update', $order), orderPayload());

    Queue::assertNotPushed(SendOrderStatusNotification::class);
});

test('only transitions to committed or accepted dispatch a status notification', function (string $from, string $to, bool $isNotified) {
    Queue::fake();
    $order = Order::factory()->create([
        'user_id' => $this->orderer->id,
        'role_id' => $this->role->id,
        'state' => $from,
    ]);

    $this->actingAs($this->materialwart)
        ->post(route('orders.manage.transition', $order), ['to' => $to]);

    if ($isNotified) {
        Queue::assertPushed(SendOrderStatusNotification::class, 1);
    } else {
        Queue::assertNotPushed(SendOrderStatusNotification::class);
    }
})->with([
    'committed to accepted' => [Committed::class, 'accepted', true],
    'accepted to delivered' => [Accepted::class, 'delivered', false],
    'committed to stored' => [Committed::class, 'stored', false],
]);

test('the status notification mails the orderer and the material responsibles', function () {
    Mail::fake();
    config(['mail.material_responsibles' => 'lager@example.com, chef@example.com']);
    $order = Order::factory()->create([
        'user_id' => $this->orderer->id,
        'role_id' => $this->role->id,
        'state' => Accepted::class,
    ]);

    (new SendOrderStatusNotification($order, 'accepted'))->handle();

    Mail::assertSent(OrderStatusChanged::class, fn (OrderStatusChanged $mail) => $mail->hasTo($this->orderer->email)
        && $mail->hasTo('lager@example.com')
        && $mail->hasTo('chef@example.com'));
});

test('the status mail can be rendered for every other status', function () {
    $order = Order::factory()->create([
        'user_id' => $this->orderer->id,
        'role_id' => $this->role->id,
        'state' => Delivered::class,
        'event' => 'Herbstlager',
    ]);

    expect((new OrderStatusChanged($order, 'delivered'))->render())->toContain('Herbstlager');
});
