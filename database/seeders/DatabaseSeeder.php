<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ItemGroupSeeder::class,
            UnitsSeeder::class,
        ]);

        // First seed roles and permissions
        $this->call(RolesAndPermissionsSeeder::class);

        // Then seed users (which will use the roles)
        $this->call(UserSeeder::class);
    }
}
