<?php

namespace Tests\Feature\Interaction;

use App\Enums\InterestStatus;
use App\Models\MemberProfile;
use App\Models\User;
use App\Notifications\Interest\InterestAcceptedNotification;
use App\Notifications\Interest\InterestReceivedNotification;
use App\Services\Interaction\InterestService;
use App\Services\Matching\ProfileVisibilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InterestTest extends TestCase
{
    use RefreshDatabase;

    private function member(string $gender = 'male'): array
    {
        $user = User::factory()->create();
        $profile = MemberProfile::factory()->create(['user_id' => $user->id, 'gender' => $gender, 'status' => 'verified']);

        return [$user, $profile];
    }

    public function test_member_can_send_interest_and_receiver_is_notified(): void
    {
        Notification::fake();
        [$senderUser, $sender] = $this->member('male');
        [, $receiver] = $this->member('female');

        $this->actingAs($senderUser)
            ->post(route('member.interests.store'), ['profile' => $receiver->uuid, 'message' => 'Hello'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('interests', [
            'sender_profile_id' => $sender->id,
            'receiver_profile_id' => $receiver->id,
            'status' => 'sent',
        ]);
        Notification::assertSentTo($receiver->user, InterestReceivedNotification::class);
    }

    public function test_duplicate_interest_is_rejected(): void
    {
        [, $sender] = $this->member('male');
        [, $receiver] = $this->member('female');
        $service = app(InterestService::class);

        $service->send($sender, $receiver);

        $this->expectException(ValidationException::class);
        $service->send($sender, $receiver);
    }

    public function test_cannot_send_interest_to_self(): void
    {
        [, $me] = $this->member('male');

        $this->expectException(ValidationException::class);
        app(InterestService::class)->send($me, $me);
    }

    public function test_accepting_interest_connects_and_unlocks_contact(): void
    {
        Notification::fake();
        [$senderUser, $sender] = $this->member('male');
        [$receiverUser, $receiver] = $this->member('female');

        $interest = app(InterestService::class)->send($sender, $receiver);

        $this->actingAs($receiverUser)
            ->post(route('member.interests.accept', ['interest' => $interest->uuid]))
            ->assertSessionHasNoErrors();

        $this->assertSame(InterestStatus::Accepted, $interest->fresh()->status);
        $this->assertTrue(app(ProfileVisibilityService::class)->areConnected($sender, $receiver));
        Notification::assertSentTo($sender->user, InterestAcceptedNotification::class);
    }

    public function test_only_receiver_can_accept(): void
    {
        [$senderUser, $sender] = $this->member('male');
        [, $receiver] = $this->member('female');
        $interest = app(InterestService::class)->send($sender, $receiver);

        $this->actingAs($senderUser)
            ->post(route('member.interests.accept', ['interest' => $interest->uuid]))
            ->assertForbidden();
    }

    public function test_sender_can_withdraw_a_pending_interest(): void
    {
        [$senderUser, $sender] = $this->member('male');
        [, $receiver] = $this->member('female');
        $interest = app(InterestService::class)->send($sender, $receiver);

        $this->actingAs($senderUser)
            ->post(route('member.interests.withdraw', ['interest' => $interest->uuid]))
            ->assertSessionHasNoErrors();

        $this->assertSame(InterestStatus::Withdrawn, $interest->fresh()->status);
    }
}
