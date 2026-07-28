<?php

namespace App\Services\Interaction;

use App\Enums\InterestStatus;
use App\Events\Interest\InterestAccepted;
use App\Events\Interest\InterestDeclined;
use App\Events\Interest\InterestReceived;
use App\Models\Interest;
use App\Models\MemberProfile;
use App\Services\Matching\ProfileVisibilityService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Governs the respectful interest workflow. Contact details stay hidden until a
 * mutual accept. Guards against self-interest, blocked pairs, audience rules,
 * duplicate sends, and re-sending after a decline.
 */
class InterestService
{
    public function __construct(private readonly ProfileVisibilityService $visibility) {}

    public function send(MemberProfile $sender, MemberProfile $receiver, ?string $message = null): Interest
    {
        if ($sender->id === $receiver->id) {
            throw ValidationException::withMessages(['interest' => 'You cannot send an interest to yourself.']);
        }

        if ($this->visibility->isBlockedBetween($sender, $receiver)) {
            throw ValidationException::withMessages(['interest' => 'This action is not available for this profile.']);
        }

        $this->assertAudienceAllows($sender, $receiver);

        $existing = Interest::query()
            ->where('sender_profile_id', $sender->id)
            ->where('receiver_profile_id', $receiver->id)
            ->first();

        if ($existing !== null) {
            return $this->handleExisting($existing);
        }

        // If the receiver had previously sent an interest, accepting is the
        // natural path — but we still create the record so both directions exist.
        $interest = new Interest([
            'sender_profile_id' => $sender->id,
            'receiver_profile_id' => $receiver->id,
            'status' => InterestStatus::Sent,
            'message' => $message ? mb_substr($message, 0, 500) : null,
        ]);
        $interest->company_id = $sender->company_id;
        $interest->save();

        InterestReceived::dispatch($interest);

        return $interest;
    }

    public function accept(Interest $interest, MemberProfile $actor): Interest
    {
        $this->assertReceiver($interest, $actor);
        $this->assertPending($interest);

        $interest->forceFill(['status' => InterestStatus::Accepted, 'responded_at' => Carbon::now()])->save();

        InterestAccepted::dispatch($interest);

        return $interest;
    }

    public function decline(Interest $interest, MemberProfile $actor): Interest
    {
        $this->assertReceiver($interest, $actor);
        $this->assertPending($interest);

        $interest->forceFill(['status' => InterestStatus::Declined, 'responded_at' => Carbon::now()])->save();

        InterestDeclined::dispatch($interest);

        return $interest;
    }

    public function withdraw(Interest $interest, MemberProfile $actor): Interest
    {
        if ($interest->sender_profile_id !== $actor->id) {
            abort(403);
        }
        $this->assertPending($interest);

        $interest->forceFill(['status' => InterestStatus::Withdrawn, 'responded_at' => Carbon::now()])->save();

        return $interest;
    }

    private function handleExisting(Interest $existing): Interest
    {
        return match ($existing->status) {
            InterestStatus::Sent => throw ValidationException::withMessages(['interest' => 'You have already sent an interest to this member.']),
            InterestStatus::Accepted => throw ValidationException::withMessages(['interest' => 'You are already connected with this member.']),
            InterestStatus::Declined => throw ValidationException::withMessages(['interest' => 'Your earlier interest was not taken forward.']),
            InterestStatus::Withdrawn => $this->resend($existing),
        };
    }

    private function resend(Interest $interest): Interest
    {
        $interest->forceFill(['status' => InterestStatus::Sent, 'responded_at' => null])->save();
        InterestReceived::dispatch($interest);

        return $interest;
    }

    private function assertAudienceAllows(MemberProfile $sender, MemberProfile $receiver): void
    {
        $audience = $receiver->preferences?->interest_from ?? 'members';

        $allowed = match ($audience) {
            'everyone', 'members' => true,
            'verified' => $sender->is_verified,
            'premium' => $this->visibility->isPremium($sender),
            default => true,
        };

        if (! $allowed) {
            throw ValidationException::withMessages([
                'interest' => 'This member only accepts interests from '.str_replace('_', ' ', $audience).' members.',
            ]);
        }
    }

    private function assertReceiver(Interest $interest, MemberProfile $actor): void
    {
        if ($interest->receiver_profile_id !== $actor->id) {
            abort(403);
        }
    }

    private function assertPending(Interest $interest): void
    {
        if (! $interest->status->isPending()) {
            throw ValidationException::withMessages(['interest' => 'This interest has already been responded to.']);
        }
    }
}
