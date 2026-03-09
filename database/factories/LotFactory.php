<?php

namespace Database\Factories;

use App\Enums\LotStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lot>
 */
class LotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('??-###')),
            'block' => strtoupper(fake()->randomLetter()),
            'description' => fake()->optional()->sentence(),
            'area_sqm' => fake()->randomFloat(2, 200, 2000),
            'price' => fake()->randomFloat(2, 50000, 500000),
            'status' => LotStatus::Available,
        ];
    }

    public function available(): static
    {
        return $this->state(['status' => LotStatus::Available]);
    }

    public function reserved(): static
    {
        return $this->state(['status' => LotStatus::Reserved]);
    }

    public function sold(): static
    {
        return $this->state(['status' => LotStatus::Sold]);
    }
}
