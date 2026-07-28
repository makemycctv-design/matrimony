<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds the default subscription plans and a sample coupon. Prices are in
 * paise (₹ * 100). Idempotent by slug.
 */
class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free', 'tier' => 'free', 'price_paise' => 0, 'duration_days' => 3650,
                'description' => 'Get started and explore matches.',
                'features' => ['Create & verify profile', 'Daily match suggestions', 'Send up to 5 interests/day'],
                'max_interests_per_day' => 5, 'max_profile_views_per_day' => 20, 'sort_order' => 1,
            ],
            [
                'name' => 'Basic', 'tier' => 'basic', 'price_paise' => 99900, 'duration_days' => 30,
                'description' => 'More visibility and unlimited interests.',
                'features' => ['Everything in Free', 'Unlimited interests', 'Advanced search filters'],
                'advanced_search' => true, 'sort_order' => 2,
            ],
            [
                'name' => 'Premium', 'tier' => 'premium', 'price_paise' => 199900, 'duration_days' => 90,
                'description' => 'Connect directly and chat after mutual interest.',
                'features' => ['Everything in Basic', 'View contact details', 'Chat after mutual interest', 'Profile highlight'],
                'contact_view_access' => true, 'messaging_access' => true, 'advanced_search' => true,
                'profile_highlight' => true, 'is_featured' => true, 'sort_order' => 3,
            ],
            [
                'name' => 'VIP', 'tier' => 'vip', 'price_paise' => 499900, 'duration_days' => 90,
                'description' => 'Priority everything with a relationship manager.',
                'features' => ['Everything in Premium', 'Profile boost', 'Priority verification', 'Relationship manager'],
                'contact_view_access' => true, 'messaging_access' => true, 'advanced_search' => true,
                'profile_highlight' => true, 'profile_boost' => true, 'verification_priority' => true, 'sort_order' => 4,
            ],
        ];

        foreach ($plans as $data) {
            SubscriptionPlan::updateOrCreate(
                ['company_id' => null, 'slug' => strtolower($data['name'])],
                array_merge(['gst_percent' => 18, 'currency' => 'INR', 'is_active' => true], $data),
            );
        }

        Coupon::firstOrCreate(
            ['code' => 'WELCOME10'],
            [
                'type' => 'percent', 'value' => 10, 'max_discount_paise' => 50000,
                'min_amount_paise' => 0, 'per_user_limit' => 1, 'is_active' => true,
                'starts_at' => Carbon::now()->subDay(), 'expires_at' => Carbon::now()->addYear(),
            ],
        );
    }
}
