<?php

namespace Database\Factories;

use App\Enums\ProposalStatus;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Proposal>
 */
class ProposalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'status' => ProposalStatus::Draft,
            'total_price' => fake()->randomFloat(2, 50000, 1000000),
            'notes' => fake()->optional()->paragraph(),
            'expires_at' => fake()->optional()->dateTimeBetween('now', '+60 days'),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => ProposalStatus::Draft]);
    }

    public function sent(): static
    {
        return $this->state(['status' => ProposalStatus::Sent]);
    }

    public function accepted(): static
    {
        return $this->state(['status' => ProposalStatus::Accepted]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => ProposalStatus::Rejected]);
    }
}
