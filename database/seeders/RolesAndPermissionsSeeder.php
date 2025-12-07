<?php

namespace Database\Seeders;

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
        // Rollen erstellen und Berechtigungen zuweisen
        Role::create(['name' => 'Materialwart']);
        Role::create(['name' => 'Abteilungsleitung']);
        Role::create(['name' => 'Bezirk']);
        Role::create(['name' => 'Biberstufe']);
        Role::create(['name' => 'Wolfsstufe']);
        Role::create(['name' => 'Pfadistufe']);
        Role::create(['name' => 'Piostufe']);
        Role::create(['name' => 'Roverstufe']);
        Role::create(['name' => 'Leitende Aetna']);
        Role::create(['name' => 'Leitende Biberstein']);
        Role::create(['name' => 'Leitende Phönix']);
        Role::create(['name' => 'Leitende Saturn']);
    }
}
