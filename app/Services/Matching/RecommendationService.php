<?php

namespace App\Services\Matching;

use App\Models\MemberProfile;
use App\Models\PartnerPreference;
use App\Models\RecommendedMatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Produces explainable "Recommended for You" matches by scoring the eligible
 * candidate pool against a member's preferences. Results can be served live or
 * materialized into recommended_matches by a scheduled job for instant reads.
 */
class RecommendationService
{
    private const POOL_CAP = 300;

    private const STORE_LIMIT = 60;

    public function __construct(
        private readonly SearchService $search,
        private readonly MatchScoringService $scoring,
    ) {}

    /**
     * Live top-N recommendations: [{profile, score, reasons}, ...].
     *
     * @return Collection<int, array{profile:MemberProfile, score:int, reasons:list<string>}>
     */
    public function generate(MemberProfile $seeker, int $limit = 12): Collection
    {
        $pool = $this->search
            ->baseQuery($seeker, $this->hardFilters($seeker->partnerPreference))
            ->with('partnerPreference')
            ->orderByDesc('last_active_at')
            ->limit(self::POOL_CAP)
            ->get();

        return $pool
            ->map(function (MemberProfile $candidate) use ($seeker) {
                $result = $this->scoring->score($seeker, $candidate);

                return ['profile' => $candidate, 'score' => $result['score'], 'reasons' => $result['reasons']];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /** Materialize recommendations into the cache table for a member. */
    public function refreshFor(MemberProfile $seeker): int
    {
        $ranked = $this->generate($seeker, self::STORE_LIMIT);

        RecommendedMatch::where('member_profile_id', $seeker->id)->delete();

        $rows = $ranked->map(fn (array $r) => [
            'company_id' => $seeker->company_id,
            'member_profile_id' => $seeker->id,
            'matched_profile_id' => $r['profile']->id,
            'score' => $r['score'],
            'reasons' => json_encode($r['reasons']),
            'computed_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ])->all();

        if (! empty($rows)) {
            RecommendedMatch::insert($rows);
        }

        return count($rows);
    }

    /**
     * Read materialized recommendations (falls back to a live computation when
     * the cache is empty, e.g. right after signup).
     *
     * @return Collection<int, array{profile:MemberProfile, score:int, reasons:list<string>}>
     */
    public function recommended(MemberProfile $seeker, int $limit = 12): Collection
    {
        $cached = RecommendedMatch::query()
            ->where('member_profile_id', $seeker->id)
            ->with(['matched.religion', 'matched.motherTongue', 'matched.profession', 'matched.education', 'matched.city', 'matched.state', 'matched.primaryPhoto', 'matched.preferences'])
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

        if ($cached->isEmpty()) {
            return $this->generate($seeker, $limit);
        }

        return $cached
            ->filter(fn (RecommendedMatch $m) => $m->matched !== null)
            ->map(fn (RecommendedMatch $m) => ['profile' => $m->matched, 'score' => $m->score, 'reasons' => $m->reasons ?? []])
            ->values();
    }

    /**
     * Hard filters kept intentionally lenient — preference details refine the
     * *score*, not the pool, so members always see recommendations.
     *
     * @return array<string, mixed>
     */
    private function hardFilters(?PartnerPreference $pref): array
    {
        if ($pref === null) {
            return [];
        }

        return array_filter([
            'gender' => $pref->preferred_gender,
            'age_min' => $pref->age_min,
            'age_max' => $pref->age_max,
            'only_verified' => $pref->only_verified,
            'only_with_photo' => $pref->only_with_photo,
        ], fn ($v) => $v !== null && $v !== false);
    }
}
