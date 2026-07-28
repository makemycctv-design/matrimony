<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MemberProfile;
use App\Models\Message;
use App\Services\Interaction\ReportService;
use App\Services\Messaging\MessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    public function __construct(
        private readonly MessageService $messages,
    ) {}

    public function index(Request $request): Response
    {
        $me = $request->user()->ensureProfile();

        return Inertia::render('member/messages', [
            'conversations' => $this->conversationList($me),
            'active' => null,
        ]);
    }

    public function show(Request $request, Conversation $conversation): Response
    {
        $me = $request->user()->ensureProfile();
        abort_unless($conversation->includes($me->id), 403);

        $this->messages->markRead($conversation, $me);
        $conversation->load(['memberOne', 'memberTwo']);
        $other = $conversation->member_one_id === $me->id ? $conversation->memberTwo : $conversation->memberOne;

        $messages = $conversation->messages()->where('is_hidden', false)->get()->map(fn (Message $m) => [
            'uuid' => $m->uuid,
            'body' => $m->body,
            'mine' => $m->sender_profile_id === $me->id,
            'created_at' => $m->created_at?->toIso8601String(),
            'flagged' => $m->is_flagged,
        ]);

        return Inertia::render('member/messages', [
            'conversations' => $this->conversationList($me),
            'active' => [
                'uuid' => $conversation->uuid,
                'other' => $other ? ['uuid' => $other->uuid, 'display_name' => $other->display_name] : null,
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function conversationList(MemberProfile $me): Collection
    {
        return Conversation::forMember($me->id)
            ->with(['memberOne.primaryPhoto', 'memberTwo.primaryPhoto', 'messages' => fn ($q) => $q->latest('id')->limit(1)])
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function (Conversation $c) use ($me) {
                $other = $c->member_one_id === $me->id ? $c->memberTwo : $c->memberOne;
                $last = $c->messages->first();
                $readAt = $c->member_one_id === $me->id ? $c->member_one_read_at : $c->member_two_read_at;

                return [
                    'uuid' => $c->uuid,
                    'other' => $other ? [
                        'uuid' => $other->uuid,
                        'display_name' => $other->display_name,
                        'photo_url' => $other->primaryPhoto?->isApproved() ? $other->primaryPhoto->thumbUrl() : null,
                    ] : null,
                    'last_message' => $last?->body,
                    'last_message_at' => $c->last_message_at?->toIso8601String(),
                    'unread' => $last && $last->sender_profile_id !== $me->id && (! $readAt || $last->created_at->gt($readAt)),
                ];
            });
    }

    public function start(Request $request): RedirectResponse
    {
        $validated = $request->validate(['profile' => ['required', Rule::exists('member_profiles', 'uuid')]]);
        $me = $request->user()->ensureProfile();
        $target = MemberProfile::where('uuid', $validated['profile'])->firstOrFail();

        $conversation = $this->messages->startOrGet($me, $target);

        return redirect()->route('member.messages.show', ['conversation' => $conversation->uuid]);
    }

    public function send(Request $request, Conversation $conversation): RedirectResponse
    {
        $validated = $request->validate(['body' => ['required', 'string', 'max:2000']]);
        $me = $request->user()->ensureProfile();

        $this->messages->send($conversation, $me, $validated['body']);

        return back();
    }

    public function report(Request $request, Message $message, ReportService $reports): RedirectResponse
    {
        $validated = $request->validate(['details' => ['nullable', 'string', 'max:1000']]);
        $me = $request->user()->ensureProfile();
        abort_unless($message->conversation->includes($me->id), 403);

        $message->forceFill(['is_flagged' => true])->save();

        $sender = MemberProfile::find($message->sender_profile_id);
        if ($sender && $sender->id !== $me->id) {
            $reports->report($me, $sender, 'offensive_behaviour', $validated['details'] ?? 'Reported message');
        }

        return back()->with('success', 'Message reported to our moderation team.');
    }
}
