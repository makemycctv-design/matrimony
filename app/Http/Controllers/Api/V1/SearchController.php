<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Search\SearchRequest;
use App\Models\MemberProfile;
use App\Services\Matching\MatchScoringService;
use App\Services\Matching\ProfileVisibilityService;
use App\Services\Matching\SearchService;
use App\Support\Profile\ProfileCardPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends ApiController
{
    public function __construct(
        private readonly SearchService $search,
        private readonly ProfileCardPresenter $presenter,
        private readonly MatchScoringService $scoring,
        private readonly ProfileVisibilityService $visibility,
    ) {}

    public function index(SearchRequest $request): JsonResponse
    {
        $viewer = $request->user()->ensureProfile();
        $viewer->loadMissing('partnerPreference');

        $results = $this->search->search($viewer, $request->filters(), $request->input('sort', 'relevance'));
        $ctx = $this->presenter->contextFor($viewer, collect($results->items())->pluck('id')->all());

        return $this->paginated($results, function (MemberProfile $p) use ($viewer, $ctx) {
            $score = $this->scoring->score($viewer, $p);

            return $this->presenter->card($p, $viewer, array_merge($ctx, ['score' => $score['score'], 'reasons' => $score['reasons']]));
        });
    }

    public function show(Request $request, MemberProfile $profile): JsonResponse
    {
        $viewer = $request->user()->ensureProfile();
        abort_unless($this->visibility->canView($viewer, $profile), 404);

        $ctx = $this->presenter->contextFor($viewer, [$profile->id]);

        return $this->ok($this->presenter->detail($profile, $viewer, $ctx));
    }
}
