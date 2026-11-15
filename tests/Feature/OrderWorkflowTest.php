<?php

use App\Enums\TransitionScope;
use App\Jobs\SendOrderStatusNotification;
use App\Models\Item;
use App\Models\ItemGroup;
use App\Models\Order;
use App\Models\OrderTransition;
use App\Models\User;
use App\Services\OrderWorkflow;
use App\States\Order\Accepted;
use App\States\Order\Committed;
use App\States\Order\Stored;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->role = Role::findByName('Wolfsstufe');
    $this->otherRole = Role::findByName('Pfadistufe');

    $this->member = User::factory()->create();
    $this->member->assignRole($this->role);

    $this->materialwart = User::factory()->create();
    $this->materialwart->assignRole('Materialwart');
});

/**
 * Configure a transition, the scopes are keyed by role id and replace the seeded ones.
 *
 * @param  array<string, bool>  $attributes
 * @param  array<int, TransitionScope>  $scopes
 */
function configureTransition(string $from, string $to, array $attributes = [], array $scopes = []): void
{
    $transition = OrderTransition::updateOrCreate(['from_state' => $from, 'to_state' => $to], $attributes);
    $transition->roles()->sync(array_map(fn (TransitionScope $scope): array => ['scope' => $scope->value], $scopes));

    app(OrderWorkflow::class)->flush();
}

/**
 * The settings form payload that keeps the current configuration.
 *
 * @param  array<string, array<string, mixed>>  $overrides
 * @return array<string, mixed>
 */
function workflowPayload(array $overrides = []): array
{
    $transitions = [];

    foreach (app(OrderWorkflow::class)->transitions() as $key => $transition) {
        $transitions[$key] = [
            'enabled' => $transition['isEnabled'] ? '1' : '0',
            'mail' => $transition['sendsMail'] ? '1' : '0',
            'roles' => array_map(fn (TransitionScope $scope): string => $scope->value, $transition['scopes']),
        ];
    }

    return ['transitions' => array_replace_recursive($transitions, $overrides)];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function workflowOrderPayload(array $overrides = []): array
{
    $item = Item::factory()->create(['item_group_id' => ItemGroup::factory()->create()->id, 'stock' => 100, 'lot' => 1]);

    return array_merge([
        'event' => 'Sommerlager',
        'event_start' => now()->addDays(3)->format('Y-m-d'),
        'event_end' => now()->addDays(5)->format('Y-m-d'),
        'role_id' => test()->role->id,
        'user_id' => test()->member->id,
        'items' => [$item->id => 2],
        'action' => 'order',
    ], $overrides);
}

test('the seeded defaults follow the order permissions of the units', function () {
    $transitions = app(OrderWorkflow::class)->transitions();
    $editingRole = Role::findByName('Abteilungsleitung');

    expect($transitions['delivered->returned']['scopes'][$editingRole->id])->toBe(TransitionScope::All)
        ->and($transitions['stored->committed']['scopes'][$this->role->id])->toBe(TransitionScope::Own)
        ->and($transitions['committed->accepted']['scopes'])->not->toHaveKey($this->role->id)
        ->and($transitions['committed->accepted']['sendsMail'])->toBeTrue()
        ->and($transitions['accepted->delivered']['sendsMail'])->toBeFalse();
});

test('applying the defaults again keeps the configured scopes', function () {
    configureTransition('committed', 'accepted', [], [$this->role->id => TransitionScope::Unit]);
    configureTransition('stored', 'committed');

    app(OrderWorkflow::class)->applyDefaults();

    $transitions = app(OrderWorkflow::class)->transitions();

    expect($transitions['committed->accepted']['scopes'])->toBe([$this->role->id => TransitionScope::Unit])
        ->and($transitions['stored->committed']['scopes'])->not->toHaveKey($this->role->id);
});

test('a unit may only move the orders within its scope', function (?TransitionScope $scope, string $orderOf, bool $isAllowed) {
    configureTransition('committed', 'accepted', [], $scope === null ? [] : [$this->role->id => $scope]);
    $colleague = User::factory()->create();

    $order = Order::factory()->create([
        'state' => Committed::class,
        'user_id' => $orderOf === 'member' ? $this->member->id : $colleague->id,
        'role_id' => $orderOf === 'unit' ? $this->role->id : $this->otherRole->id,
    ]);

    expect($this->member->can('transition', [$order, 'accepted']))->toBe($isAllowed);
})->with([
    'no scope, own order' => [null, 'member', false],
    'no scope, order of the unit' => [null, 'unit', false],
    'own, own order' => [TransitionScope::Own, 'member', true],
    'own, order of the unit' => [TransitionScope::Own, 'unit', false],
    'unit, order of the unit' => [TransitionScope::Unit, 'unit', true],
    'unit, own order for another unit' => [TransitionScope::Unit, 'member', false],
    'unit, foreign order' => [TransitionScope::Unit, 'foreign', false],
    'all, foreign order' => [TransitionScope::All, 'foreign', true],
]);

test('a scope does not allow transitions the state machine does not know', function () {
    configureTransition('committed', 'accepted', [], [$this->role->id => TransitionScope::All]);
    $order = Order::factory()->create(['state' => Stored::class, 'user_id' => $this->member->id, 'role_id' => $this->role->id]);

    expect($this->member->can('transition', [$order, 'accepted']))->toBeFalse();
});

test('a member without access to the order management performs a transition granted to their unit', function () {
    configureTransition('committed', 'stored', [], [$this->role->id => TransitionScope::Unit]);
    $order = Order::factory()->create(['state' => Committed::class, 'user_id' => $this->member->id, 'role_id' => $this->role->id]);

    $this->actingAs($this->member)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertSee('Ablehnen (zu Entwurf)');

    $this->actingAs($this->member)
        ->post(route('orders.manage.transition', $order), ['to' => 'stored'])
        ->assertSessionHas('success');

    expect($order->fresh()->state)->toBeInstanceOf(Stored::class);
});

test('a transition that is not granted to the unit is forbidden and not offered', function () {
    $order = Order::factory()->create(['state' => Committed::class, 'user_id' => $this->member->id, 'role_id' => $this->role->id]);

    $this->actingAs($this->member)
        ->get(route('orders.show', $order))
        ->assertOk()
        ->assertDontSee('Ablehnen (zu Entwurf)');

    $this->actingAs($this->member)
        ->post(route('orders.manage.transition', $order), ['to' => 'stored'])
        ->assertForbidden();

    expect($order->fresh()->state)->toBeInstanceOf(Committed::class);
});

test('a disabled transition is rejected and not offered, even for the materialwart', function () {
    configureTransition('committed', 'accepted', ['is_enabled' => false]);
    $order = Order::factory()->create(['state' => Committed::class, 'user_id' => $this->member->id, 'role_id' => $this->role->id]);

    $this->actingAs($this->materialwart)
        ->get(route('orders.manage.edit', $order))
        ->assertOk()
        ->assertDontSee('Akzeptieren')
        ->assertSee('Ablehnen (zu Entwurf)');

    $this->actingAs($this->materialwart)
        ->post(route('orders.manage.transition', $order), ['to' => 'accepted'])
        ->assertSessionHas('error', 'Dieser Statusübergang ist nicht erlaubt.');

    expect($order->fresh()->state)->toBeInstanceOf(Committed::class);
});

test('a transition dispatches a status notification only when its mail is switched on', function (string $state, string $to, bool $sendsMail) {
    Queue::fake();
    $order = Order::factory()->create(['state' => $state, 'user_id' => $this->member->id, 'role_id' => $this->role->id]);
    configureTransition($order->state::getMorphClass(), $to, ['sends_mail' => $sendsMail]);

    $this->actingAs($this->materialwart)
        ->post(route('orders.manage.transition', $order), ['to' => $to])
        ->assertSessionHas('success');

    if ($sendsMail) {
        Queue::assertPushed(SendOrderStatusNotification::class, fn (SendOrderStatusNotification $job): bool => $job->statusName === $to);
    } else {
        Queue::assertNotPushed(SendOrderStatusNotification::class);
    }
})->with([
    'handing out with mail' => [Accepted::class, 'delivered', true],
    'accepting without mail' => [Committed::class, 'accepted', false],
]);

test('an order cannot be submitted when the unit may not submit orders', function () {
    configureTransition('stored', 'committed');

    $this->actingAs($this->member)
        ->get(route('orders.create'))
        ->assertOk()
        ->assertDontSee('Bestellung senden');

    $this->actingAs($this->member)
        ->post(route('orders.store'), workflowOrderPayload())
        ->assertSessionHasErrors(['state' => 'Dieser Statusübergang ist nicht erlaubt.']);

    assertDatabaseCount('orders', 0);
});

test('a draft stays a draft when the unit may not submit it', function () {
    configureTransition('stored', 'committed');
    $order = Order::factory()->create(['state' => Stored::class, 'user_id' => $this->member->id, 'role_id' => $this->role->id]);

    $this->actingAs($this->member)
        ->put(route('orders.update', $order), workflowOrderPayload())
        ->assertSessionHasErrors('state');

    expect($order->fresh()->state)->toBeInstanceOf(Stored::class);
});

test('saving an order keeps its state when the user may not turn it back into a draft', function (bool $mayReject, string $expectedState) {
    $editingRole = Role::findByName('Abteilungsleitung');
    $editor = User::factory()->create();
    $editor->assignRole($editingRole);
    configureTransition('committed', 'stored', [], $mayReject ? [$editingRole->id => TransitionScope::All] : []);
    $order = Order::factory()->create(['state' => Committed::class, 'user_id' => $editor->id, 'role_id' => $editingRole->id]);

    $this->actingAs($editor)
        ->put(route('orders.update', $order), workflowOrderPayload(['action' => 'save', 'user_id' => $editor->id, 'role_id' => $editingRole->id]))
        ->assertSessionHasNoErrors();

    expect($order->fresh()->state)->toBeInstanceOf($expectedState);
})->with([
    'allowed' => [true, Stored::class],
    'not allowed' => [false, Committed::class],
]);

test('the materialwart sees the order workflow settings', function () {
    $this->actingAs($this->materialwart)
        ->get(route('settings.order-workflow.edit'))
        ->assertOk()
        ->assertSee('Wolfsstufe')
        ->assertSee('Akzeptieren');
});

test('users without the manage settings permission cannot see or change the order workflow', function () {
    $this->actingAs($this->member)
        ->get(route('settings.order-workflow.edit'))
        ->assertForbidden();

    $this->actingAs($this->member)
        ->put(route('settings.order-workflow.update'), workflowPayload(['committed->accepted' => ['enabled' => '0']]))
        ->assertForbidden();

    expect(app(OrderWorkflow::class)->isEnabled('committed', 'accepted'))->toBeTrue();
});

test('saving the order workflow stores the switches and the scopes of the units', function () {
    $this->actingAs($this->materialwart)
        ->put(route('settings.order-workflow.update'), workflowPayload([
            'committed->accepted' => ['enabled' => '0'],
            'accepted->delivered' => ['mail' => '1', 'roles' => [$this->role->id => 'unit']],
            'stored->committed' => ['roles' => [$this->role->id => '']],
        ]))
        ->assertRedirect(route('settings.order-workflow.edit'))
        ->assertSessionHas('success');

    $handingOut = OrderTransition::where(['from_state' => 'accepted', 'to_state' => 'delivered'])->sole();
    $submitting = OrderTransition::where(['from_state' => 'stored', 'to_state' => 'committed'])->sole();

    assertDatabaseHas('order_transitions', ['from_state' => 'committed', 'to_state' => 'accepted', 'is_enabled' => false]);
    assertDatabaseHas('order_transition_role', ['order_transition_id' => $handingOut->id, 'role_id' => $this->role->id, 'scope' => 'unit']);
    expect($handingOut->sends_mail)->toBeTrue()
        ->and($submitting->roles()->whereKey($this->role->id)->exists())->toBeFalse();
});

test('saving the order workflow rejects an unknown scope', function () {
    $this->actingAs($this->materialwart)
        ->put(route('settings.order-workflow.update'), workflowPayload([
            'committed->accepted' => ['roles' => [$this->role->id => 'everything']],
        ]))
        ->assertSessionHasErrors("transitions.committed->accepted.roles.{$this->role->id}");

    expect(app(OrderWorkflow::class)->transitions()['committed->accepted']['scopes'])->not->toHaveKey($this->role->id);
});

test('saving the order workflow ignores a scope for the materialwart', function () {
    $privilegedRole = Role::findByName('Materialwart');

    $this->actingAs($this->materialwart)
        ->put(route('settings.order-workflow.update'), workflowPayload([
            'committed->accepted' => ['roles' => [$privilegedRole->id => 'own']],
        ]))
        ->assertSessionHasNoErrors();

    expect(app(OrderWorkflow::class)->transitions()['committed->accepted']['scopes'])->not->toHaveKey($privilegedRole->id);
});
