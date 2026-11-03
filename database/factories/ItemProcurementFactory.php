<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\ItemProcurement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemProcurement>
 */
class ItemProcurementFactory extends Factory
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
            'procured_on' => fake()->dateTimeBetween('-5 years')->format('Y-m-d'),
            'quantity' => fake()->numberBetween(1, 10),
            'supplier' => fake()->optional()->company(),
            'unit_price_cents' => fake()->optional()->numberBetween(500, 50000),
            'invoice_number' => fake()->optional()->bothify('RE-####'),
            'warranty_until' => null,
            'notes' => null,
            'added_to_stock' => false,
        ];
    }
}
