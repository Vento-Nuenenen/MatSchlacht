<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Order;
use App\Models\OrderedItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderedItem>
 */
class OrderedItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'item_id' => Item::factory(),
            'quantity' => fake()->numberBetween(1, 5),
            'confirmed' => 0,
            'delivered' => 0,
            'returned' => 0,
            'repair' => 0,
            'broken' => 0,
        ];
    }
}
