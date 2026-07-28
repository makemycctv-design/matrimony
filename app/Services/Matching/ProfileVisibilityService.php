<?php

namespace App\Services\Matching;

use App\Enums\InterestStatus;
use App\Models\BlockedProfile;
use App\Models\Interest;
use App\Models\MemberProfile;

/**
 * Central authority for "who can see what". Enforces blocking (both
 * directions), audience-based visibility (everyone/members/verified/premium/
 * connected/none), premium/verified gating, and the "hide details until
 * interest accepted" rule. Search and profile-detail both route through here so
 * privacy is never bypassed by the UI.
 */
class ProfileVisibilityService
{
    /** Two profiles are "connected" once an interest between them is accepted. */
    public function areConnected(MemberProfile $a, MemberProfile $b): bool
    {
        return Interest::query()
            ->where('status', InterestStatus::Accepted->value)
            ->where(function ($q) use ($a, $b) {
                $q->where(fn ($s) => $s->where('sender_profile_id', $a->id)->where('receiver_profile_id', $b->id))
                    ->orWhere(fn ($s) => $s->where('sender_profile_id', $b->id)->where('receiver_profile_id', $a->id));
            })
            ->exists();
    }

    public function isBlockedBetween(MemberProfile $a, MemberProfile $b): bool
    {
        return BlockedProfile::query()
            ->where(fn ($q) => $q->where('member_profile_id', $a->id)->where('blocked_profile_id', $b->id))
            ->orWhere(fn ($q) => $q->where('member_profile_id', $b->id)->where('blocked_profile_id', $a->id))
            ->exists();
    }

    /**
     * Whether the target profile may be surfaced to the viewer at all
     * (search results / direct detail access).
     */
    public function canView(MemberProfile $viewer, MemberProfile $target): bool
    {
        if ($viewer->id === $target->id) {
            return true;
        }

        if (! $target->isDiscoverable()) {
            return false;
        }

        if ($this->isBlockedBetween($viewer, $target)) {
            return false;
        }

        $prefs = $target->preferences;

        if ($prefs !== null) {
            if (! $prefs->appear_in_search) {
                return false;
            }
            if ($prefs->visible_to_verified_only && ! $viewer->is_verified) {
                return false;
            }
            if ($prefs->visible_to_premium_only && ! $this->isPremium($viewer)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether a specific detail field is visible to the viewer.
     * $field ∈ photo | contact | horoscope | income.
     */
    public function canSeeField(MemberProfile $viewer, MemberProfile $target, string $field): bool
    {
        if ($viewer->id === $target->id) {
            return true;
        }

        $prefs = $target->preferences;
        $connected = $this->areConnected($viewer, $target);

        // Contact is additionally gated by the "hide until accepted" switch.
        if ($field === 'contact' && $prefs?->hide_details_until_interest_accepted && ! $connected) {
            return false;
        }

        $audience = match ($field) {
            'photo' => $prefs?->photo_visibility ?? 'members',
            'contact' => $prefs?->contact_visibility ?? 'connected',
            'horoscope' => $prefs?->horoscope_visibility ?? 'connected',
            'income' => $prefs?->income_visibility ?? 'members',
            default => 'members',
        };

        return $this->audienceAllows($audience, $viewer, $connected);
    }

    private function audienceAllows(string $audience, MemberProfile $viewer, bool $connected): bool
    {
        return match ($audience) {
            'everyone', 'members' => true,
            'verified' => $viewer->is_verified,
            'premium' => $this->isPremium($viewer),
            'connected' => $connected,
            'none' => false,
            default => true,
        };
    }

    public function isPremium(MemberProfile $profile): bool
    {
        // Subscription-based premium is wired up in Phase 4; role is the bridge.
        return $profile->user?->hasRole('Premium Member') ?? false;
    }
}
