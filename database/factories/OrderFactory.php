<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\States\Order\Stored;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event' => fake()->sentence(3),
            'event_start' => now()->addDays(fake()->numberBetween(1, 10)),
            'event_end' => now()->addDays(fake()->numberBetween(11, 20)),
            'state' => Stored::class,
            'user_id' => User::factory(),
            'role_id' => Role::first()->id ?? Role::create(['name' => 'TestRole'])->id,
        ];
    }
}
