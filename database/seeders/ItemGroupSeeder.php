<?php

namespace Database\Seeders;

use App\Models\ItemGroup;
use Illuminate\Database\Seeder;

class ItemGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $groups = [
            'Beleuchtungsmaterial',
            'Diverses',
            'Küchenutensilien',
            'Pioniermaterial',
            'Werkzeug',
            'Zelte',
        ];

        foreach ($groups as $group) {
            ItemGroup::firstOrCreate([
                'name' => $group,
            ]);
        }
    }
}
