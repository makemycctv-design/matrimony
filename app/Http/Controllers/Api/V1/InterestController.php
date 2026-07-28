<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Models\Interest;
use App\Models\MemberProfile;
use App\Services\Interaction\InterestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InterestController extends ApiController
{
    public function __construct(private readonly InterestService $interests) {}

    public function index(Request $request): JsonResponse
    {
        $me = $request->user()->ensureProfile();

        return $this->ok([
            'sent' => $me->sentInterests()->with('receiver:id,uuid,profile_code,first_name,name_display,last_name')->latest('id')->get()
                ->map(fn (Interest $i) => $this->row($i, $i->receiver)),
            'received' => $me->receivedInterests()->with('sender:id,uuid,profile_code,first_name,name_display,last_name')->latest('id')->get()
                ->map(fn (Interest $i) => $this->row($i, $i->sender)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profile' => ['required', Rule::exists('member_profiles', 'uuid')],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $me = $request->user()->ensureProfile();
        $target = MemberProfile::where('uuid', $validated['profile'])->firstOrFail();

        $interest = $this->interests->send($me, $target, $validated['message'] ?? null);

        return $this->ok(['uuid' => $interest->uuid, 'status' => $interest->status->value], 'Interest sent.', 201);
    }

    public function accept(Request $request, Interest $interest): JsonResponse
    {
        $this->interests->accept($interest, $request->user()->ensureProfile());

        return $this->message('Interest accepted.');
    }

    public function decline(Request $request, Interest $interest): JsonResponse
    {
        $this->interests->decline($interest, $request->user()->ensureProfile());

        return $this->message('Interest declined.');
    }

    public function withdraw(Request $request, Interest $interest): JsonResponse
    {
        $this->interests->withdraw($interest, $request->user()->ensureProfile());

        return $this->message('Interest withdrawn.');
    }

    private function row(Interest $interest, ?MemberProfile $party): array
    {
        return [
            'uuid' => $interest->uuid,
            'status' => $interest->status->value,
            'message' => $interest->message,
            'created_at' => $interest->created_at?->toIso8601String(),
            'profile' => $party ? ['uuid' => $party->uuid, 'display_name' => $party->display_name, 'code' => $party->profile_code] : null,
        ];
    }
}
