<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'legal_name' => $name.' Pvt Ltd',
            'email' => fake()->companyEmail(),
            'phone' => '+91'.fake()->numerify('9#########'),
            'default_locale' => 'en',
            'supported_locales' => ['en', 'ml'],
            'timezone' => 'Asia/Kolkata',
            'currency' => 'INR',
            'is_active' => true,
        ];
    }
}
