<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement(['Basic', 'Premium', 'VIP']);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(4),
            'tier' => strtolower($name),
            'description' => 'Test plan',
            'price_paise' => fake()->numberBetween(99900, 499900),
            'gst_percent' => 18,
            'currency' => 'INR',
            'duration_days' => 90,
            'trial_days' => 0,
            'contact_view_access' => true,
            'messaging_access' => true,
            'advanced_search' => true,
            'is_active' => true,
        ];
    }

    public function free(): static
    {
        return $this->state(fn () => [
            'name' => 'Free', 'slug' => 'free', 'tier' => 'free', 'price_paise' => 0,
            'contact_view_access' => false, 'messaging_access' => false, 'advanced_search' => false,
        ]);
    }
}
