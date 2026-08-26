<?php

namespace Database\Factories;

use App\Models\Organisation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            /*
             * V1.0.10 multi-tenancy: users always belong to an
             * organisation. Tests overwhelmingly exercise ONE
             * organisation, so every factory user joins the first
             * existing organisation by default; isolation tests create
             * further organisations explicitly via forOrganisation().
             */
            'organisation_id' => static fn (): int => (int) (
                Organisation::query()->value('id')
                ?? Organisation::factory()->create()->id
            ),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'role' => 'property_manager',
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Attach the user to a specific organisation.
     */
    public function forOrganisation(Organisation $organisation): static
    {
        return $this->state(fn (array $attributes) => [
            'organisation_id' => $organisation->id,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
