<?php

namespace Database\Factories;

use App\Models\ItemGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemGroup>
 */
class ItemGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
        ];
    }
}
