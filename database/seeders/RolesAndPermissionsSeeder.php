<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $models = ['users', 'itemgroups', 'items', 'orders', 'roles', 'itemcollections', 'storage'];
        $actions = ['view', 'create', 'edit', 'delete'];

        $permissions = [
            'manage azure mappings',
            'manage settings',
            'authenticated',
            'manage orders',
            'edit own orders',
            'delete own orders',
        ];

        foreach ($models as $model) {
            foreach ($actions as $action) {
                $permissions[] = "$action $model";
            }
        }

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Create Roles and assign permissions
        $materialwart = Role::findOrCreate('Materialwart');
        $materialwart->givePermissionTo(Permission::all());

        $abteilungsleitung = Role::findOrCreate('Abteilungsleitung');
        $abteilungsleitung->givePermissionTo([
            'view users',
            'view itemgroups', 'view items', 'view storage',
            'view orders', 'create orders', 'edit orders',
        ]);

        $leitung = [
            'Bezirk', 'Biberstufe', 'Wolfsstufe', 'Pfadistufe', 'Piostufe', 'Roverstufe',
            'Leitende Aetna', 'Leitende Biberstein', 'Leitende Phönix', 'Leitende Saturn',
        ];

        foreach ($leitung as $roleName) {
            $role = Role::findOrCreate($roleName);
            $role->givePermissionTo([
                'view itemgroups', 'view items', 'view storage',
                'view orders', 'create orders',
                'edit own orders', 'delete own orders',
            ]);
        }
    }
}
