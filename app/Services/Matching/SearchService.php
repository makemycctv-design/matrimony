<?php

namespace App\Services\Matching;

use App\Enums\ProfileStatus;
use App\Models\MemberProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Builds and runs discovery queries. Applies hard privacy rules (discoverable
 * status, appear_in_search, blocking both ways, verified/premium-only gating)
 * before any user filter, so results can never leak a hidden profile.
 */
class SearchService
{
    public function __construct(private readonly ProfileVisibilityService $visibility) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function search(MemberProfile $seeker, array $filters, string $sort = 'relevance', int $perPage = 12): LengthAwarePaginator
    {
        $query = $this->baseQuery($seeker, $filters);
        $this->applyFilters($query, $filters);
        $this->applySort($query, $sort);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Eligible candidate pool with all mandatory privacy/safety constraints
     * applied. Reused by the recommendation engine.
     *
     * @param  array<string, mixed>  $filters
     */
    public function baseQuery(MemberProfile $seeker, array $filters = []): Builder
    {
        $excluded = array_merge([$seeker->id], $seeker->blockedIds(), $seeker->blockedByIds());

        $query = MemberProfile::query()
            ->with(['religion', 'motherTongue', 'profession', 'education', 'city', 'state', 'primaryPhoto', 'preferences'])
            ->whereIn('status', [
                ProfileStatus::Verified->value,
                ProfileStatus::Submitted->value,
                ProfileStatus::UnderReview->value,
            ])
            ->whereKeyNot($excluded)
            // Respect appear_in_search (profiles without a preferences row are visible).
            ->where(function (Builder $q) {
                $q->whereHas('preferences', fn (Builder $p) => $p->where('appear_in_search', true))
                    ->orWhereDoesntHave('preferences');
            });

        // Verified-only / premium-only visibility gating relative to the seeker.
        if (! $seeker->is_verified) {
            $query->whereDoesntHave('preferences', fn (Builder $p) => $p->where('visible_to_verified_only', true));
        }
        if (! $this->visibility->isPremium($seeker)) {
            $query->whereDoesntHave('preferences', fn (Builder $p) => $p->where('visible_to_premium_only', true));
        }

        // Default to the opposite gender unless the seeker explicitly filters.
        $gender = $filters['gender'] ?? $seeker->gender?->opposite()->value;
        if ($gender) {
            $query->where('gender', $gender);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        // Age -> date_of_birth range.
        if (! empty($filters['age_min']) || ! empty($filters['age_max'])) {
            $max = (int) ($filters['age_max'] ?? 100);
            $min = (int) ($filters['age_min'] ?? 18);
            $query->whereBetween('date_of_birth', [
                Carbon::today()->subYears($max + 1)->addDay(),
                Carbon::today()->subYears($min),
            ]);
        }

        if (! empty($filters['height_min'])) {
            $query->where('height_cm', '>=', (int) $filters['height_min']);
        }
        if (! empty($filters['height_max'])) {
            $query->where('height_cm', '<=', (int) $filters['height_max']);
        }

        $this->whereInIf($query, 'marital_status', $filters['marital_statuses'] ?? null);
        $this->whereInIf($query, 'religion_id', $filters['religion_ids'] ?? null);
        $this->whereInIf($query, 'caste_id', $filters['caste_ids'] ?? null);
        $this->whereInIf($query, 'mother_tongue_id', $filters['mother_tongue_ids'] ?? null);
        $this->whereInIf($query, 'education_id', $filters['education_ids'] ?? null);
        $this->whereInIf($query, 'profession_id', $filters['profession_ids'] ?? null);
        $this->whereInIf($query, 'diet', $filters['diets'] ?? null);

        foreach (['country_id', 'state_id', 'district_id', 'city_id'] as $loc) {
            if (! empty($filters[$loc])) {
                $query->where($loc, (int) $filters[$loc]);
            }
        }

        if (! empty($filters['income_min'])) {
            $query->where('annual_income_max', '>=', (int) $filters['income_min']);
        }
        if (! empty($filters['income_max'])) {
            $query->where('annual_income_min', '<=', (int) $filters['income_max']);
        }

        if (! empty($filters['only_verified'])) {
            $query->where('is_verified', true);
        }

        if (! empty($filters['only_with_photo'])) {
            $query->whereHas('photos', fn (Builder $p) => $p->where('status', 'approved'));
        }

        if (! empty($filters['only_premium'])) {
            $query->whereHas('user.roles', fn (Builder $r) => $r->where('name', 'Premium Member'));
        }

        if (! empty($filters['recently_active'])) {
            $query->where('last_active_at', '>=', Carbon::now()->subDays(7));
        }

        if (! empty($filters['online'])) {
            $query->where('last_active_at', '>=', Carbon::now()->subMinutes(15));
        }

        if (! empty($filters['keyword'])) {
            $kw = trim((string) $filters['keyword']);
            $query->where(function (Builder $q) use ($kw) {
                $q->where('first_name', 'like', "%{$kw}%")
                    ->orWhere('last_name', 'like', "%{$kw}%")
                    ->orWhere('profile_code', 'like', "%{$kw}%")
                    ->orWhere('about_me', 'like', "%{$kw}%");
            });
        }
    }

    private function whereInIf(Builder $query, string $column, ?array $values): void
    {
        $values = array_filter((array) ($values ?? []));

        if (! empty($values)) {
            $query->whereIn($column, $values);
        }
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'newest' => $query->latest('id'),
            'age_asc' => $query->orderByDesc('date_of_birth'),   // younger first
            'age_desc' => $query->orderBy('date_of_birth'),      // older first
            // "relevance" and "active" both lead with recent activity; per-card
            // compatibility scores are layered on by the controller/presenter.
            default => $query->orderByDesc('last_active_at')->orderByDesc('is_verified')->latest('id'),
        };
    }
}
