<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'event' => fake()->sentence(3),
            'event_start' => now()->addDays(fake()->numberBetween(1, 10)),
            'event_end' => now()->addDays(fake()->numberBetween(11, 20)),
            'status' => \App\States\Order\Stored::class,
            'user_id' => User::factory(),
            'role_id' => Role::first()?->id ?? Role::create(['name' => 'TestRole'])->id,
        ];
    }
}
