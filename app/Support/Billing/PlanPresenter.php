<?php

namespace App\Support\Billing;

use App\Models\SubscriptionPlan;

/**
 * Serializes subscription plans for public pricing, comparison and checkout.
 * Exposes both paise (authoritative) and rupee display values.
 */
class PlanPresenter
{
    public function plan(SubscriptionPlan $plan): array
    {
        return [
            'uuid' => $plan->uuid,
            'name' => $plan->name,
            'tier' => $plan->tier,
            'description' => $plan->description,
            'price_paise' => $plan->price_paise,
            'price' => $plan->price_paise / 100,
            'gst_percent' => (float) $plan->gst_percent,
            'duration_days' => $plan->duration_days,
            'trial_days' => $plan->trial_days,
            'is_free' => $plan->isFree(),
            'is_featured' => $plan->is_featured,
            'features' => $plan->features ?? [],
            'limits' => [
                'contact_view_access' => $plan->contact_view_access,
                'messaging_access' => $plan->messaging_access,
                'advanced_search' => $plan->advanced_search,
                'profile_boost' => $plan->profile_boost,
                'profile_highlight' => $plan->profile_highlight,
                'verification_priority' => $plan->verification_priority,
                'max_interests_per_day' => $plan->max_interests_per_day,
                'max_profile_views_per_day' => $plan->max_profile_views_per_day,
            ],
        ];
    }

    /**
     * @param  iterable<SubscriptionPlan>  $plans
     * @return list<array<string, mixed>>
     */
    public function collection(iterable $plans): array
    {
        $out = [];
        foreach ($plans as $plan) {
            $out[] = $this->plan($plan);
        }

        return $out;
    }
}
