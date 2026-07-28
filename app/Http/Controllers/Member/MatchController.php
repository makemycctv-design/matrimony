<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use App\Services\Matching\RecommendationService;
use App\Services\Matching\SearchService;
use App\Support\Profile\ProfileCardPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The discovery hub: "Recommended for You", "New Matches", "Near You",
 * and "Verified Matches" — each respecting privacy and blocking rules.
 */
class MatchController extends Controller
{
    public function __construct(
        private readonly RecommendationService $recommendations,
        private readonly SearchService $search,
        private readonly ProfileCardPresenter $presenter,
    ) {}

    public function index(Request $request): Response
    {
        $viewer = $request->user()->ensureProfile();
        $viewer->loadMissing('partnerPreference');

        $recommended = $this->recommendations->recommended($viewer, 12);

        $newMatches = $this->search->baseQuery($viewer)->latest('id')->limit(8)->get();
        $nearYou = $this->search->baseQuery($viewer)
            ->when($viewer->city_id, fn ($q) => $q->where('city_id', $viewer->city_id), fn ($q) => $q->where('state_id', $viewer->state_id))
            ->limit(8)->get();
        $verified = $this->search->baseQuery($viewer)->where('is_verified', true)->latest('verified_at')->limit(8)->get();

        // One context lookup across every profile shown on the page.
        $allIds = collect($recommended->pluck('profile.id'))
            ->merge($newMatches->pluck('id'))
            ->merge($nearYou->pluck('id'))
            ->merge($verified->pluck('id'))
            ->unique()->values()->all();
        $ctx = $this->presenter->contextFor($viewer, $allIds);

        return Inertia::render('member/matches', [
            'sections' => [
                'recommended' => $recommended->map(fn (array $m) => $this->presenter->card($m['profile'], $viewer, array_merge($ctx, [
                    'score' => $m['score'],
                    'reasons' => $m['reasons'],
                ])))->all(),
                'new_matches' => $this->cards($newMatches, $viewer, $ctx),
                'near_you' => $this->cards($nearYou, $viewer, $ctx),
                'verified' => $this->cards($verified, $viewer, $ctx),
            ],
            'joined_recently_since' => Carbon::now()->subDays(30)->toIso8601String(),
        ]);
    }

    /**
     * @param  Collection<int, MemberProfile>  $profiles
     * @param  array<string, mixed>  $ctx
     * @return list<array<string, mixed>>
     */
    private function cards(Collection $profiles, MemberProfile $viewer, array $ctx): array
    {
        return $profiles->map(fn (MemberProfile $p) => $this->presenter->card($p, $viewer, $ctx))->all();
    }
}
