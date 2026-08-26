<?php

namespace Database\Factories;

use App\Models\Organisation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organisation>
 */
class OrganisationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'status' => 'active',
            'trial_ends_on' => null,
        ];
    }

    /**
     * An organisation inside its introductory Professional trial.
     */
    public function onTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_on' => now()->addDays(30)->toDateString(),
        ]);
    }

    /**
     * A suspended organisation.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
