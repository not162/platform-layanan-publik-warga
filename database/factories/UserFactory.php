<?php

namespace Database\Factories;

use App\Enums\UserRole;
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
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::WARGA->value,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
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

    public function superadmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::SUPERADMIN->value,
        ]);
    }

    public function admin(array $permissions = []): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::ADMIN->value,
        ])->afterCreating(function (User $user) use ($permissions) {
            foreach ($permissions as $permission) {
                $user->givePermission($permission);
            }
        });
    }

    public function warga(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::WARGA->value,
        ]);
    }

    public function ketuaRt(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::KETUA_RT->value,
        ]);
    }

    public function sekretaris(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::SEKRETARIS->value,
        ]);
    }

    public function bendahara(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::BENDAHARA->value,
        ]);
    }

    public function petugasKeamanan(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::PETUGAS_KEAMANAN->value,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
