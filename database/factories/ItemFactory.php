<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'stock' => fake()->numberBetween(0, 100),
            'repair' => 0,
            'consumable' => fake()->boolean(),
            'item_group_id' => ItemGroup::factory(),
        ];
    }
}
