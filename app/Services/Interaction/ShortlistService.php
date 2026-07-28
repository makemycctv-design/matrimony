<?php

namespace App\Services\Interaction;

use App\Models\MemberProfile;
use App\Models\Shortlist;

class ShortlistService
{
    /** Toggle a target in the member's shortlist. Returns true if now shortlisted. */
    public function toggle(MemberProfile $owner, MemberProfile $target): bool
    {
        if ($owner->id === $target->id) {
            return false;
        }

        $existing = Shortlist::query()
            ->where('member_profile_id', $owner->id)
            ->where('shortlisted_profile_id', $target->id)
            ->first();

        if ($existing !== null) {
            $existing->delete();

            return false;
        }

        $shortlist = new Shortlist([
            'member_profile_id' => $owner->id,
            'shortlisted_profile_id' => $target->id,
        ]);
        $shortlist->company_id = $owner->company_id;
        $shortlist->save();

        return true;
    }
}
