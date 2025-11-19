<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Berechtigungen erstellen
        Permission::create(['name' => 'manage users']);
        Permission::create(['name' => 'manage items']);

        // Rollen erstellen und Berechtigungen zuweisen
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(['manage users', 'manage items']);

        $matChefRole = Role::create(['name' => 'matchef']);
        $matChefRole->givePermissionTo(['view reports']);

        $userRole = Role::create(['name' => 'user']);
        $userRole->givePermissionTo(['view reports']);
    }
}
