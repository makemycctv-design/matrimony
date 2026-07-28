<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchRequest;
use App\Models\MemberProfile;
use App\Services\Matching\MatchScoringService;
use App\Services\Matching\SearchService;
use App\Services\Profile\ProfileOptionsService;
use App\Support\Profile\ProfileCardPresenter;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $search,
        private readonly MatchScoringService $scoring,
        private readonly ProfileCardPresenter $presenter,
    ) {}

    public function index(SearchRequest $request, ProfileOptionsService $options): Response
    {
        $viewer = $request->user()->ensureProfile();
        $viewer->loadMissing('partnerPreference');

        $filters = $request->filters();
        $sort = $request->input('sort', 'relevance');

        $results = $this->search->search($viewer, $filters, $sort);

        $ids = collect($results->items())->pluck('id')->all();
        $ctx = $this->presenter->contextFor($viewer, $ids);

        $results->getCollection()->transform(function (MemberProfile $profile) use ($viewer, $ctx) {
            $score = $this->scoring->score($viewer, $profile);

            return $this->presenter->card($profile, $viewer, array_merge($ctx, [
                'score' => $score['score'],
                'reasons' => $score['reasons'],
            ]));
        });

        return Inertia::render('member/search', [
            'results' => $results,
            'filters' => (object) $filters,
            'sort' => $sort,
            'options' => $options->all(),
            'savedSearches' => $viewer->savedSearches()->latest('id')->get(['uuid', 'name', 'criteria']),
        ]);
    }
}
