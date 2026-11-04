<?php

namespace App\Services;

use App\Models\User;

/**
 * The navigation of the app, used by the sidebar (tablet, desktop), the bottom tab bar and the
 * «Mehr» sheet (smartphone). Items are only listed when the user may use them and their
 * component is enabled.
 */
class NavigationService
{
    /**
     * How many items the bottom tab bar shows before the «Mehr» tab.
     */
    public const MOBILE_TAB_COUNT = 4;

    public function __construct(protected ComponentManager $components) {}

    /**
     * @return array<int, array{title: ?string, items: array<int, array{label: string, icon: string, route: string, active: array<int, string>, mobilePriority: ?int}>}>
     */
    public function sections(User $user): array
    {
        $sections = [
            [
                'title' => null,
                'items' => [
                    $this->item('Übersicht', 'fa-house', 'overwatch', ['overwatch'], 1),
                ],
            ],
            [
                'title' => 'Bestellungen',
                'items' => [
                    $this->item('Bestellen', 'fa-cart-shopping', 'orders.index', ['orders.index', 'orders.create', 'orders.show', 'orders.edit'], 2,
                        $user->canAny(['view orders', 'create orders', 'edit orders', 'delete orders'])),
                    $this->item('Verwalten', 'fa-clipboard-check', 'orders.manage', ['orders.manage*'], 3, $user->can('manage orders')),
                ],
            ],
            [
                'title' => 'Material',
                'items' => [
                    $this->item('Material', 'fa-box-open', 'items.index', ['items.*'], 4, $user->canAny(['view items', 'create items', 'edit items', 'delete items'])),
                    $this->item('Lager', 'fa-warehouse', 'storage-units.index', ['storage-units.*'], 5,
                        $this->components->isEnabled('storage') && $user->can('view storage')),
                    $this->item('Wartung', 'fa-screwdriver-wrench', 'maintenance.index', ['maintenance.*'], 6,
                        $this->components->isEnabled('maintenance') && $user->can('view items')),
                    $this->item('Kollektionen', 'fa-layer-group', 'item-collections.index', ['item-collections.*'], null,
                        $this->components->isEnabled('item_collections') && $user->canAny(['view itemcollections', 'create itemcollections', 'edit itemcollections', 'delete itemcollections'])),
                    $this->item('Materialgruppen', 'fa-tags', 'item-groups.index', ['item-groups.*'], null,
                        $user->canAny(['view itemgroups', 'create itemgroups', 'update itemgroups', 'delete itemgroups'])),
                ],
            ],
            [
                'title' => 'Administration',
                'items' => [
                    $this->item('Benutzer', 'fa-users', 'users.index', ['users.*'], null, $user->canAny(['view users', 'create users', 'update users', 'delete users'])),
                    $this->item('Einheiten & Rechte', 'fa-user-shield', 'roles.index', ['roles.*'], null, $user->canAny(['view roles', 'create roles', 'update roles', 'delete roles'])),
                    $this->item('Azure Mappings', 'fa-diagram-project', 'azure-group-role-mappings.index', ['azure-group-role-mappings.*'], null,
                        $this->components->isEnabled('azure_login') && $user->can('manage azure mappings')),
                    $this->item('Hitobito-Regeln', 'fa-id-card', 'hitobito-access-rules.index', ['hitobito-access-rules.*'], null,
                        $this->components->isEnabled('hitobito_login') && $user->can('manage hitobito access')),
                    $this->item('Hitobito-Test', 'fa-vial', 'hitobito-test.index', ['hitobito-test.*'], null,
                        $this->components->isEnabled('hitobito_login') && $user->can('manage hitobito access')),
                    $this->item('Einstellungen', 'fa-sliders', 'settings.components.index', ['settings.*'], null, $user->can('manage settings')),
                ],
            ],
        ];

        return collect($sections)
            ->map(fn (array $section) => [...$section, 'items' => array_values(array_filter($section['items']))])
            ->filter(fn (array $section) => $section['items'] !== [])
            ->values()
            ->all();
    }

    /**
     * The items of the bottom tab bar on smartphones, in order of their priority.
     *
     * @return array<int, array{label: string, icon: string, route: string, active: array<int, string>, mobilePriority: ?int}>
     */
    public function mobileTabs(User $user): array
    {
        return collect($this->sections($user))
            ->flatMap(fn (array $section) => $section['items'])
            ->filter(fn (array $item) => $item['mobilePriority'] !== null)
            ->sortBy('mobilePriority')
            ->take(self::MOBILE_TAB_COUNT)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $active  Route name patterns for which the item is highlighted.
     * @return array{label: string, icon: string, route: string, active: array<int, string>, mobilePriority: ?int}|null
     */
    protected function item(string $label, string $icon, string $route, array $active, ?int $mobilePriority, bool $isVisible = true): ?array
    {
        if (! $isVisible) {
            return null;
        }

        return ['label' => $label, 'icon' => $icon, 'route' => $route, 'active' => $active, 'mobilePriority' => $mobilePriority];
    }
}
