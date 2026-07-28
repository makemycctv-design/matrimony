<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'country_code' => '+91',
            'mobile' => (string) fake()->unique()->numerify('9#########'),
            'mobile_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => UserStatus::Active,
            'locale' => 'en',
            'terms_accepted_at' => now(),
            'privacy_accepted_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'mobile_verified_at' => null,
            'status' => UserStatus::Pending,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => ['status' => UserStatus::Suspended]);
    }
}
