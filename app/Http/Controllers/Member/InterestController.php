<?php

namespace App\Http\Controllers\Member;

use App\Enums\InterestStatus;
use App\Http\Controllers\Controller;
use App\Models\Interest;
use App\Models\MemberProfile;
use App\Services\Interaction\InterestService;
use App\Support\Profile\ProfileCardPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InterestController extends Controller
{
    public function __construct(
        private readonly InterestService $interests,
        private readonly ProfileCardPresenter $presenter,
    ) {}

    public function index(Request $request): Response
    {
        $viewer = $request->user()->ensureProfile();

        $sent = $viewer->sentInterests()->with($this->relations('receiver'))->latest('id')->get();
        $received = $viewer->receivedInterests()->with($this->relations('sender'))->latest('id')->get();

        $ids = $sent->pluck('receiver_profile_id')->merge($received->pluck('sender_profile_id'))->unique()->values()->all();
        $ctx = $this->presenter->contextFor($viewer, $ids);

        return Inertia::render('member/interests', [
            'sent' => $sent->map(fn (Interest $i) => $this->row($i, $i->receiver, $viewer, $ctx))->all(),
            'received' => $received->map(fn (Interest $i) => $this->row($i, $i->sender, $viewer, $ctx))->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'profile' => ['required', Rule::exists('member_profiles', 'uuid')],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $viewer = $request->user()->ensureProfile();
        $target = MemberProfile::where('uuid', $validated['profile'])->firstOrFail();

        $this->interests->send($viewer, $target, $validated['message'] ?? null);

        return back()->with('success', 'Your interest has been sent.');
    }

    public function accept(Request $request, Interest $interest): RedirectResponse
    {
        $this->interests->accept($interest, $request->user()->ensureProfile());

        return back()->with('success', 'Interest accepted — you are now connected.');
    }

    public function decline(Request $request, Interest $interest): RedirectResponse
    {
        $this->interests->decline($interest, $request->user()->ensureProfile());

        return back()->with('success', 'Interest declined.');
    }

    public function withdraw(Request $request, Interest $interest): RedirectResponse
    {
        $this->interests->withdraw($interest, $request->user()->ensureProfile());

        return back()->with('success', 'Interest withdrawn.');
    }

    private function relations(string $party): array
    {
        return [
            "$party.religion", "$party.motherTongue", "$party.profession", "$party.education",
            "$party.city", "$party.state", "$party.primaryPhoto", "$party.preferences",
        ];
    }

    private function row(Interest $interest, ?MemberProfile $party, MemberProfile $viewer, array $ctx): array
    {
        return [
            'uuid' => $interest->uuid,
            'status' => $interest->status->value,
            'message' => $interest->message,
            'created_at' => $interest->created_at?->toIso8601String(),
            'responded_at' => $interest->responded_at?->toIso8601String(),
            'is_pending' => $interest->status === InterestStatus::Sent,
            'profile' => $party ? $this->presenter->card($party, $viewer, $ctx) : null,
        ];
    }
}
