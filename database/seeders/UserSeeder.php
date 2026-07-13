<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::updateOrCreate(['email' => 'admin@example.com'], [
            'firstname' => 'Admin',
            'lastname' => 'User',
            'scoutname' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('Materialwart');

        // Create regular user
        $user = User::updateOrCreate(['email' => 'user@example.com'], [
            'firstname' => 'Regular',
            'lastname' => 'User',
            'scoutname' => 'Scout',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('Abteilungsleitung');

        // Create additional users with the user role
        User::factory(5)->create()->each(function ($user) {
            $user->assignRole('Pfadistufe');
        });
    }
}
