<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\BlockedProfile;
use App\Models\MemberProfile;
use App\Services\Interaction\BlockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BlockController extends Controller
{
    public function __construct(private readonly BlockService $blocks) {}

    public function index(Request $request): Response
    {
        $viewer = $request->user()->ensureProfile();

        $blocked = BlockedProfile::query()
            ->where('member_profile_id', $viewer->id)
            ->with('blocked:id,uuid,profile_code,first_name,name_display,last_name')
            ->latest('id')
            ->get()
            ->map(fn (BlockedProfile $b) => [
                'uuid' => $b->blocked?->uuid,
                'profile_code' => $b->blocked?->profile_code,
                'display_name' => $b->blocked?->display_name,
                'reason' => $b->reason,
                'blocked_at' => $b->created_at?->toIso8601String(),
            ]);

        return Inertia::render('member/blocked', ['blocked' => $blocked]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'profile' => ['required', Rule::exists('member_profiles', 'uuid')],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $viewer = $request->user()->ensureProfile();
        $target = MemberProfile::where('uuid', $validated['profile'])->firstOrFail();

        $this->blocks->block($viewer, $target, $validated['reason'] ?? null);

        return back()->with('success', 'Profile blocked. They can no longer see or contact you.');
    }

    public function destroy(Request $request, MemberProfile $profile): RedirectResponse
    {
        $this->blocks->unblock($request->user()->ensureProfile(), $profile);

        return back()->with('success', 'Profile unblocked.');
    }
}
