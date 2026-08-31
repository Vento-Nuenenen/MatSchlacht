<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemContent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemContent>
 */
class ItemContentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'name' => fake()->word(),
            'quantity' => fake()->randomDigit().'x',
        ];
    }
}
