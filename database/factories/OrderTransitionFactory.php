<?php

namespace Database\Factories;

use App\Models\OrderTransition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderTransition>
 */
class OrderTransitionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_state' => 'committed',
            'to_state' => 'accepted',
            'is_enabled' => true,
            'sends_mail' => true,
        ];
    }

    public function between(string $fromState, string $toState): static
    {
        return $this->state(fn (): array => [
            'from_state' => $fromState,
            'to_state' => $toState,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (): array => ['is_enabled' => false]);
    }

    public function withoutMail(): static
    {
        return $this->state(fn (): array => ['sends_mail' => false]);
    }
}
