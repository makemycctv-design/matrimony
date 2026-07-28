<?php

namespace App\Services\Messaging;

use App\Events\Message\MessageSent;
use App\Models\Conversation;
use App\Models\MemberProfile;
use App\Models\Message;
use App\Services\Matching\ProfileVisibilityService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Private messaging that is only available AFTER two members are connected
 * (a mutually accepted interest). Enforces that gate plus block checks, and
 * keeps unread state per participant.
 */
class MessageService
{
    public function __construct(private readonly ProfileVisibilityService $visibility) {}

    /** Get the existing thread for a connected pair, or create it. */
    public function startOrGet(MemberProfile $a, MemberProfile $b): Conversation
    {
        if ($a->id === $b->id) {
            throw ValidationException::withMessages(['message' => 'You cannot message yourself.']);
        }

        if ($this->visibility->isBlockedBetween($a, $b)) {
            throw ValidationException::withMessages(['message' => 'Messaging is not available for this profile.']);
        }

        if (! $this->visibility->areConnected($a, $b)) {
            throw ValidationException::withMessages(['message' => 'You can only message members after your interest is mutually accepted.']);
        }

        [$one, $two] = Conversation::orderedPair($a->id, $b->id);

        $conversation = Conversation::firstOrNew(['member_one_id' => $one, 'member_two_id' => $two]);
        if (! $conversation->exists) {
            $conversation->company_id = $a->company_id;
            $conversation->save();
        }

        return $conversation;
    }

    public function send(Conversation $conversation, MemberProfile $sender, string $body): Message
    {
        abort_unless($conversation->includes($sender->id), 403);

        if ($conversation->is_closed) {
            throw ValidationException::withMessages(['message' => 'This conversation is closed.']);
        }

        $message = new Message([
            'conversation_id' => $conversation->id,
            'sender_profile_id' => $sender->id,
            'body' => trim($body),
        ]);
        $message->company_id = $conversation->company_id;
        $message->save();

        $conversation->forceFill(['last_message_at' => Carbon::now()])->save();
        $this->markRead($conversation, $sender); // sender has read their own message

        MessageSent::dispatch($message);

        return $message;
    }

    /** Mark the conversation read for the given participant. */
    public function markRead(Conversation $conversation, MemberProfile $reader): void
    {
        $field = $conversation->member_one_id === $reader->id ? 'member_one_read_at' : 'member_two_read_at';
        $conversation->forceFill([$field => Carbon::now()])->save();
    }

    public function unreadCountFor(MemberProfile $profile): int
    {
        return Conversation::forMember($profile->id)->get()->sum(function (Conversation $c) use ($profile) {
            $readAt = $c->member_one_id === $profile->id ? $c->member_one_read_at : $c->member_two_read_at;

            return Message::where('conversation_id', $c->id)
                ->where('sender_profile_id', '!=', $profile->id)
                ->where('is_hidden', false)
                ->when($readAt, fn ($q) => $q->where('created_at', '>', $readAt))
                ->count();
        });
    }
}
