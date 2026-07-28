<?php

namespace App\Services\Payments;

use App\Models\User;

/**
 * Resolves what a member is entitled to based on their active subscription,
 * falling back to sensible free-tier limits. Consumed by feature gates
 * (contact view, messaging, advanced search) and daily quota checks.
 */
class EntitlementService
{
    /** Free-tier defaults when no paid subscription is active. */
    public const FREE = [
        'contact_view_access' => false,
        'messaging_access' => false,
        'advanced_search' => false,
        'profile_boost' => false,
        'profile_highlight' => false,
        'verification_priority' => false,
        'max_profile_views_per_day' => 20,
        'max_interests_per_day' => 5,
        'max_contact_views' => 0,
    ];

    /**
     * @return array<string, mixed>
     */
    public function for(User $user): array
    {
        $subscription = $user->activeSubscription();

        if ($subscription === null) {
            return self::FREE;
        }

        return array_merge(self::FREE, (array) $subscription->entitlements);
    }

    public function allows(User $user, string $feature): bool
    {
        return (bool) ($this->for($user)[$feature] ?? false);
    }

    /** Numeric limit for a key (null = unlimited). */
    public function limit(User $user, string $key): ?int
    {
        $value = $this->for($user)[$key] ?? null;

        return $value === null ? null : (int) $value;
    }
}
