<?php

namespace App\Services\Interaction;

use App\Enums\InterestStatus;
use App\Models\BlockedProfile;
use App\Models\Interest;
use App\Models\MemberProfile;
use App\Models\Shortlist;
use Illuminate\Support\Facades\DB;

class BlockService
{
    /**
     * Block a target. Blocking also withdraws any pending interests and removes
     * the pair from each other's shortlists so they disappear from all surfaces.
     */
    public function block(MemberProfile $owner, MemberProfile $target, ?string $reason = null): void
    {
        if ($owner->id === $target->id) {
            return;
        }

        DB::transaction(function () use ($owner, $target, $reason): void {
            $block = BlockedProfile::firstOrNew([
                'member_profile_id' => $owner->id,
                'blocked_profile_id' => $target->id,
            ]);
            $block->reason = $reason;
            $block->company_id = $owner->company_id;
            $block->save();

            // Cancel any pending interest either direction.
            Interest::query()
                ->where('status', InterestStatus::Sent->value)
                ->where(function ($q) use ($owner, $target) {
                    $q->where(fn ($s) => $s->where('sender_profile_id', $owner->id)->where('receiver_profile_id', $target->id))
                        ->orWhere(fn ($s) => $s->where('sender_profile_id', $target->id)->where('receiver_profile_id', $owner->id));
                })
                ->update(['status' => InterestStatus::Withdrawn->value]);

            // Remove from both shortlists.
            Shortlist::query()
                ->where(fn ($q) => $q->where('member_profile_id', $owner->id)->where('shortlisted_profile_id', $target->id))
                ->orWhere(fn ($q) => $q->where('member_profile_id', $target->id)->where('shortlisted_profile_id', $owner->id))
                ->delete();
        });
    }

    public function unblock(MemberProfile $owner, MemberProfile $target): void
    {
        BlockedProfile::query()
            ->where('member_profile_id', $owner->id)
            ->where('blocked_profile_id', $target->id)
            ->delete();
    }
}
