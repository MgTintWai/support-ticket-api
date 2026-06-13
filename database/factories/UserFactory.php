<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'organization_id' => null,
            'role' => UserRole::SupportAgent,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function client(?Organization $organization = null): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Client,
            'organization_id' => $organization?->id ?? Organization::factory(),
        ]);
    }

    public function agent(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::SupportAgent,
            'organization_id' => null,
        ]);
    }
}
