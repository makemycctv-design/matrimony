<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use App\Models\Shortlist;
use App\Services\Interaction\ShortlistService;
use App\Support\Profile\ProfileCardPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ShortlistController extends Controller
{
    public function __construct(
        private readonly ShortlistService $shortlists,
        private readonly ProfileCardPresenter $presenter,
    ) {}

    public function index(Request $request): Response
    {
        $viewer = $request->user()->ensureProfile();

        $items = Shortlist::query()
            ->where('member_profile_id', $viewer->id)
            ->with(['shortlisted.religion', 'shortlisted.motherTongue', 'shortlisted.profession', 'shortlisted.education', 'shortlisted.city', 'shortlisted.state', 'shortlisted.primaryPhoto', 'shortlisted.preferences'])
            ->latest('id')
            ->get();

        $ids = $items->pluck('shortlisted_profile_id')->all();
        $ctx = $this->presenter->contextFor($viewer, $ids);

        return Inertia::render('member/shortlist', [
            'profiles' => $items
                ->filter(fn (Shortlist $s) => $s->shortlisted !== null)
                ->map(fn (Shortlist $s) => $this->presenter->card($s->shortlisted, $viewer, $ctx))
                ->values()->all(),
        ]);
    }

    public function toggle(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'profile' => ['required', Rule::exists('member_profiles', 'uuid')],
        ]);

        $viewer = $request->user()->ensureProfile();
        $target = MemberProfile::where('uuid', $validated['profile'])->firstOrFail();

        $now = $this->shortlists->toggle($viewer, $target);

        return back()->with('success', $now ? 'Added to your shortlist.' : 'Removed from your shortlist.');
    }
}
