<?php

namespace App\Services;

use App\Enums\TransitionScope;
use App\Models\Order;
use App\Models\OrderTransition;
use App\Models\User;
use App\States\Order\OrderState;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * The configurable part of the order state machine: which of the transitions defined in
 * OrderState are in use, which units may perform them on which orders and which send a mail.
 */
class OrderWorkflow
{
    /**
     * The role that may perform every enabled transition on every order.
     */
    public const string PRIVILEGED_ROLE = 'Materialwart';

    /**
     * Transitions to these states send a mail unless configured otherwise.
     *
     * @var array<int, string>
     */
    protected const array DEFAULT_MAIL_STATES = ['committed', 'accepted'];

    /**
     * Button label and style of the transitions, the others are labelled with their target state.
     *
     * @var array<string, array{label: string, button: string}>
     */
    protected const array BUTTONS = [
        'stored->committed' => ['label' => 'Bestellen', 'button' => 'btn-primary'],
        'committed->stored' => ['label' => 'Ablehnen (zu Entwurf)', 'button' => 'btn-outline-danger'],
        'committed->accepted' => ['label' => 'Akzeptieren', 'button' => 'btn-primary'],
        'accepted->committed' => ['label' => 'Rückgängig (Akzeptieren)', 'button' => 'btn-outline-danger'],
        'accepted->delivered' => ['label' => 'Ausgeben', 'button' => 'btn-info text-white'],
        'delivered->accepted' => ['label' => 'Rückgängig (Ausgeben)', 'button' => 'btn-outline-danger'],
        'delivered->returned' => ['label' => 'Zurückerhalten', 'button' => 'btn-secondary'],
        'returned->delivered' => ['label' => 'Rückgängig (Zurückerhalten)', 'button' => 'btn-outline-danger'],
        'returned->archived' => ['label' => 'Archivieren/Stornieren', 'button' => 'btn-danger'],
        'stored->archived' => ['label' => 'Archivieren/Stornieren', 'button' => 'btn-danger'],
        'committed->archived' => ['label' => 'Archivieren/Stornieren', 'button' => 'btn-danger'],
        'accepted->archived' => ['label' => 'Archivieren/Stornieren', 'button' => 'btn-danger'],
    ];

    /**
     * @var array<string, array{key: string, from: string, to: string, fromLabel: string, toLabel: string, label: string, button: string, isEnabled: bool, sendsMail: bool, scopes: array<int, TransitionScope>}>|null
     */
    protected ?array $transitions = null;

    /**
     * Every transition of the state machine with its configuration, keyed by "from->to".
     *
     * @return array<string, array{key: string, from: string, to: string, fromLabel: string, toLabel: string, label: string, button: string, isEnabled: bool, sendsMail: bool, scopes: array<int, TransitionScope>}>
     */
    public function transitions(): array
    {
        if ($this->transitions !== null) {
            return $this->transitions;
        }

        $stored = OrderTransition::with('roles')->get()
            ->keyBy(fn (OrderTransition $transition): string => self::key($transition->from_state, $transition->to_state));
        $transitions = [];

        foreach (array_keys(OrderState::config()->allowedTransitions) as $key) {
            [$from, $to] = explode('->', $key);
            $transition = $stored->get($key);
            $toLabel = $this->stateLabel($to);

            $transitions[$key] = [
                'key' => $key,
                'from' => $from,
                'to' => $to,
                'fromLabel' => $this->stateLabel($from),
                'toLabel' => $toLabel,
                'label' => isset(self::BUTTONS[$key]) ? __(self::BUTTONS[$key]['label']) : $toLabel,
                'button' => self::BUTTONS[$key]['button'] ?? 'btn-secondary',
                'isEnabled' => $transition instanceof OrderTransition ? $transition->is_enabled : true,
                'sendsMail' => $transition instanceof OrderTransition ? $transition->sends_mail : $this->sendsMailByDefault($to),
                'scopes' => $transition instanceof OrderTransition ? $this->scopesOf($transition) : [],
            ];
        }

        return $this->transitions = $transitions;
    }

    public static function key(string $from, string $to): string
    {
        return "{$from}->{$to}";
    }

    public function isEnabled(string $from, string $to): bool
    {
        return $this->transitions()[self::key($from, $to)]['isEnabled'] ?? false;
    }

    public function sendsMail(string $from, string $to): bool
    {
        $transition = $this->transitions()[self::key($from, $to)] ?? null;

        return $transition !== null && $transition['isEnabled'] && $transition['sendsMail'];
    }

    /**
     * Whether the user may move the order from its current state to the given one.
     */
    public function allows(User $user, Order $order, string $to): bool
    {
        return $this->allowsBetween($user, $order, $order->state::getMorphClass(), $to);
    }

    /**
     * Whether the user may move the order between the given states, whatever state it is in.
     * Used for orders that are created in a later state than the default.
     */
    public function allowsBetween(User $user, Order $order, string $from, string $to): bool
    {
        $transition = $this->transitions()[self::key($this->stateName($from), $this->stateName($to))] ?? null;

        if ($transition === null || ! $transition['isEnabled']) {
            return false;
        }

        if ($user->hasRole(self::PRIVILEGED_ROLE)) {
            return true;
        }

        foreach ($user->roles->modelKeys() as $roleId) {
            $scope = $transition['scopes'][$roleId] ?? null;

            if ($scope instanceof TransitionScope && $scope->covers($order, $user, (int) $roleId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the user may perform the transition on any order at all, e.g. to offer
     * submitting an order that does not exist yet.
     */
    public function isPerformableBy(User $user, string $from, string $to): bool
    {
        $transition = $this->transitions()[self::key($from, $to)] ?? null;

        if ($transition === null || ! $transition['isEnabled']) {
            return false;
        }

        return $user->hasRole(self::PRIVILEGED_ROLE)
            || array_intersect_key($transition['scopes'], array_flip($user->roles->modelKeys())) !== [];
    }

    /**
     * The transitions the user may perform on the order right now.
     *
     * @return array<int, array{key: string, from: string, to: string, fromLabel: string, toLabel: string, label: string, button: string, isEnabled: bool, sendsMail: bool, scopes: array<int, TransitionScope>}>
     */
    public function availableTransitions(User $user, Order $order): array
    {
        $from = $order->state::getMorphClass();

        return array_values(array_filter(
            $this->transitions(),
            fn (array $transition): bool => $transition['from'] === $from && $this->allowsBetween($user, $order, $from, $transition['to'])
        ));
    }

    /**
     * The units whose scope is configurable. The privileged role may always perform everything.
     *
     * @return Collection<int, Role>
     */
    public function configurableRoles(): Collection
    {
        return Role::where('name', '!=', self::PRIVILEGED_ROLE)->orderBy('name')->get();
    }

    /**
     * Save the configuration of the given transitions, keyed by "from->to".
     *
     * @param  array<string, array{enabled?: mixed, mail?: mixed, roles?: array<int|string, mixed>}>  $values
     */
    public function update(array $values): void
    {
        $roleIds = $this->configurableRoles()->modelKeys();

        DB::transaction(function () use ($values, $roleIds): void {
            foreach ($this->transitions() as $key => $definition) {
                if (! array_key_exists($key, $values)) {
                    continue;
                }

                $transition = OrderTransition::updateOrCreate(
                    ['from_state' => $definition['from'], 'to_state' => $definition['to']],
                    [
                        'is_enabled' => (bool) ($values[$key]['enabled'] ?? false),
                        'sends_mail' => (bool) ($values[$key]['mail'] ?? false),
                    ]
                );

                $scopes = [];

                foreach ($values[$key]['roles'] ?? [] as $roleId => $scope) {
                    $scope = is_string($scope) ? TransitionScope::tryFrom($scope) : null;

                    if ($scope instanceof TransitionScope && in_array((int) $roleId, $roleIds, true)) {
                        $scopes[(int) $roleId] = ['scope' => $scope->value];
                    }
                }

                $transition->roles()->sync($scopes);
            }
        });

        $this->flush();
    }

    /**
     * Create the missing transitions and give units that have no scope yet the one matching
     * their permissions: managing or editing orders moves all orders, editing own orders
     * submits own drafts. Configured transitions and units are left alone.
     */
    public function applyDefaults(): void
    {
        $this->flush();
        $transitions = [];

        foreach ($this->transitions() as $key => $definition) {
            $transitions[$key] = OrderTransition::firstOrCreate(
                ['from_state' => $definition['from'], 'to_state' => $definition['to']],
                ['is_enabled' => true, 'sends_mail' => $this->sendsMailByDefault($definition['to'])]
            );
        }

        $configuredRoleIds = DB::table('order_transition_role')->distinct()->pluck('role_id')->all();

        foreach ($this->configurableRoles()->load('permissions') as $role) {
            if (in_array($role->id, $configuredRoleIds, false)) {
                continue;
            }

            $permissions = $role->permissions->pluck('name');

            if ($permissions->intersect(['manage orders', 'edit orders'])->isNotEmpty()) {
                foreach ($transitions as $transition) {
                    $transition->roles()->attach($role->id, ['scope' => TransitionScope::All->value]);
                }
            } elseif ($permissions->contains('edit own orders')) {
                $transitions[self::key('stored', 'committed')]->roles()->attach($role->id, ['scope' => TransitionScope::Own->value]);
            }
        }

        $this->flush();
    }

    public function flush(): void
    {
        $this->transitions = null;
    }

    /**
     * @return array<int, TransitionScope>
     */
    protected function scopesOf(OrderTransition $transition): array
    {
        $scopes = [];

        foreach ($transition->roles as $role) {
            $scope = TransitionScope::tryFrom((string) $role->getRelationValue('pivot')?->getAttribute('scope'));

            if ($scope instanceof TransitionScope) {
                $scopes[(int) $role->id] = $scope;
            }
        }

        return $scopes;
    }

    protected function sendsMailByDefault(string $to): bool
    {
        return in_array($to, self::DEFAULT_MAIL_STATES, true);
    }

    /**
     * The stored name of a state, given as that name or as its class.
     */
    protected function stateName(string $state): string
    {
        $stateClass = OrderState::resolveStateClass($state);

        return is_string($stateClass) && is_subclass_of($stateClass, OrderState::class) ? $stateClass::getMorphClass() : $state;
    }

    protected function stateLabel(string $state): string
    {
        $instance = OrderState::make($state, new Order);

        return $instance instanceof OrderState ? $instance->display() : $state;
    }
}
