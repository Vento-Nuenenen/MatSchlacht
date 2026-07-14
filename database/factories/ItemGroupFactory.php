<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ItemGroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array{name: string}
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
        ];
    }
}
