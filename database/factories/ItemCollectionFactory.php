<?php

namespace Database\Factories;

use App\Models\ItemCollection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ItemCollection>
 */
class ItemCollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
        ];
    }
}
