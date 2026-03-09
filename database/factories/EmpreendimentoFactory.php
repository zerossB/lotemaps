<?php

namespace Database\Factories;

use App\Enums\EmpreendimentoStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Empreendimento>
 */
class EmpreendimentoFactory extends Factory
{
    public function definition(): array
    {
        $states = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
            'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
            'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

        return [
            'name' => 'Loteamento '.fake()->unique()->words(2, true),
            'description' => fake()->optional()->paragraph(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->randomElement($states),
            'total_area' => fake()->randomFloat(2, 10000, 500000),
            'status' => EmpreendimentoStatus::Active,
            'map_lat' => null,
            'map_lng' => null,
            'map_zoom' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(['status' => EmpreendimentoStatus::Active]);
    }

    public function inactive(): static
    {
        return $this->state(['status' => EmpreendimentoStatus::Inactive]);
    }
}
