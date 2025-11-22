<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            'Abteilungsleitung',
            'Bezirk',
            'Biberstufe',
            'Leiter Aetna',
            'Leiter Biberstein',
            'Leiter Phönix',
            'Leiter Saturn',
            'Materialwart',
            'Pfadistufe',
            'Piostufe',
            'Roverstufe',
            'Wolfsstufe',
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate([
                'name' => $unit,
            ]);
        }
    }
}
