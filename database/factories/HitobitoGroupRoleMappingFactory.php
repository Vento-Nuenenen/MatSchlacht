<?php

namespace Database\Factories;

use App\Models\HitobitoGroupRoleMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HitobitoGroupRoleMapping>
 */
class HitobitoGroupRoleMappingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hitobito_group_id' => (string) fake()->unique()->numberBetween(1, 999999),
            'role_name' => 'Wolfsstufe',
        ];
    }
}
