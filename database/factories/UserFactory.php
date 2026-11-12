<?php

namespace Database\Factories;

use App\Enums\Role;
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
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'role' => Role::Mekanik,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    // Pintasan peran, contoh: User::factory()->admin()->create()
    public function admin(): static
    {
        return $this->state(fn () => ['role' => Role::Admin]);
    }

    public function mekanik(): static
    {
        return $this->state(fn () => ['role' => Role::Mekanik]);
    }

    public function kasir(): static
    {
        return $this->state(fn () => ['role' => Role::Kasir]);
    }

    public function pemilik(): static
    {
        return $this->state(fn () => ['role' => Role::Pemilik]);
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