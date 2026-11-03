<?php

namespace Database\Factories;

use App\Enums\StorageLevel;
use App\Models\StorageUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StorageUnit>
 */
class StorageUnitFactory extends Factory
{
    /**
     * Define the model's default state: a top level storage location.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'level' => StorageLevel::Location,
            'name' => fake()->unique()->city(),
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function room(): static
    {
        return $this->state(fn () => [
            'level' => StorageLevel::Room,
            'parent_id' => StorageUnit::factory(),
            'name' => 'Raum '.fake()->unique()->numberBetween(1, 9999),
        ]);
    }

    public function shelf(): static
    {
        return $this->state(fn () => [
            'level' => StorageLevel::Shelf,
            'parent_id' => StorageUnit::factory()->room(),
            'name' => 'Regal '.fake()->unique()->bothify('?##'),
        ]);
    }

    public function tier(): static
    {
        return $this->state(fn () => [
            'level' => StorageLevel::Tier,
            'parent_id' => StorageUnit::factory()->shelf(),
            'name' => 'Ebene '.fake()->unique()->numberBetween(1, 9999),
        ]);
    }

    public function bin(): static
    {
        return $this->state(fn () => [
            'level' => StorageLevel::Bin,
            'parent_id' => StorageUnit::factory()->tier(),
            'name' => 'Fach '.fake()->unique()->numberBetween(1, 9999),
        ]);
    }
}
