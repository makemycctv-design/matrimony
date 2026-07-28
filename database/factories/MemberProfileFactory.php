<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\ProfileStatus;
use App\Models\MemberProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberProfile>
 */
class MemberProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gender = fake()->randomElement([Gender::Male, Gender::Female]);

        return [
            'first_name' => fake()->firstName($gender === Gender::Male ? 'male' : 'female'),
            'last_name' => fake()->lastName(),
            'name_display' => 'first_only',
            'date_of_birth' => fake()->dateTimeBetween('-38 years', '-23 years')->format('Y-m-d'),
            'gender' => $gender,
            'marital_status' => fake()->randomElement(['never_married', 'divorced', 'widowed']),
            'height_cm' => fake()->numberBetween(150, 190),
            'physical_status' => 'none',
            'diet' => fake()->randomElement(['vegetarian', 'non_vegetarian', 'eggetarian']),
            'family_type' => fake()->randomElement(['nuclear', 'joint']),
            'about_me' => fake()->paragraph(),
            'status' => ProfileStatus::Verified,
            'is_verified' => true,
            'verified_at' => now(),
            'last_active_at' => now(),
            'completion_percentage' => fake()->numberBetween(60, 100),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => ProfileStatus::Draft,
            'is_verified' => false,
            'verified_at' => null,
        ]);
    }

    public function male(): static
    {
        return $this->state(fn () => ['gender' => Gender::Male]);
    }

    public function female(): static
    {
        return $this->state(fn () => ['gender' => Gender::Female]);
    }
}
