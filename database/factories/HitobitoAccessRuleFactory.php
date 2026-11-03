<?php

namespace Database\Factories;

use App\Models\HitobitoAccessRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HitobitoAccessRule>
 */
class HitobitoAccessRuleFactory extends Factory
{
    /**
     * Define the model's default state: a rule for one person.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hitobito_person_id' => (string) fake()->unique()->numberBetween(1, 999999),
        ];
    }
}
