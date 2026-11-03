<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemStock;
use App\Models\StorageUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemStock>
 */
class ItemStockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'storage_unit_id' => StorageUnit::factory()->bin(),
            'item_id' => Item::factory(),
            'quantity' => fake()->numberBetween(1, 20),
        ];
    }
}
