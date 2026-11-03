<?php

namespace Database\Factories;

use App\Enums\MaintenanceType;
use App\Models\Item;
use App\Models\ItemMaintenance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemMaintenance>
 */
class ItemMaintenanceFactory extends Factory
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
            'user_id' => null,
            'performed_on' => fake()->dateTimeBetween('-2 years')->format('Y-m-d'),
            'type' => fake()->randomElement(MaintenanceType::cases()),
            'quantity' => null,
            'performed_by' => fake()->optional()->name(),
            'cost_cents' => null,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
