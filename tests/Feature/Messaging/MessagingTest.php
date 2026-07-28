<?php

namespace Tests\Feature\Messaging;

use App\Enums\InterestStatus;
use App\Models\Conversation;
use App\Models\Interest;
use App\Models\MemberProfile;
use App\Models\User;
use App\Services\Messaging\MessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function member(string $gender = 'male'): array
    {
        $user = User::factory()->create();
        $profile = MemberProfile::factory()->create(['user_id' => $user->id, 'gender' => $gender, 'status' => 'verified']);

        return [$user, $profile];
    }

    private function connect(MemberProfile $a, MemberProfile $b): void
    {
        Interest::create([
            'sender_profile_id' => $a->id,
            'receiver_profile_id' => $b->id,
            'status' => InterestStatus::Accepted,
            'responded_at' => now(),
        ]);
    }

    public function test_members_cannot_message_without_a_mutual_connection(): void
    {
        [, $a] = $this->member('male');
        [, $b] = $this->member('female');

        $this->expectException(ValidationException::class);
        app(MessageService::class)->startOrGet($a, $b);
    }

    public function test_connected_members_can_exchange_messages(): void
    {
        [$userA, $a] = $this->member('male');
        [, $b] = $this->member('female');
        $this->connect($a, $b);

        $conversation = app(MessageService::class)->startOrGet($a, $b);

        $this->actingAs($userA)
            ->post(route('member.messages.send', ['conversation' => $conversation->uuid]), ['body' => 'Hello there'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('messages', ['conversation_id' => $conversation->id, 'sender_profile_id' => $a->id, 'body' => 'Hello there']);
    }

    public function test_non_participant_cannot_send_to_a_conversation(): void
    {
        [, $a] = $this->member('male');
        [, $b] = $this->member('female');
        $this->connect($a, $b);
        $conversation = app(MessageService::class)->startOrGet($a, $b);

        [$intruderUser] = $this->member('male');

        $this->actingAs($intruderUser)
            ->post(route('member.messages.send', ['conversation' => $conversation->uuid]), ['body' => 'sneaky'])
            ->assertForbidden();
    }

    public function test_conversation_pair_is_unique(): void
    {
        [, $a] = $this->member('male');
        [, $b] = $this->member('female');
        $this->connect($a, $b);

        $first = app(MessageService::class)->startOrGet($a, $b);
        $second = app(MessageService::class)->startOrGet($b, $a);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Conversation::count());
    }
}
