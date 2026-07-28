<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Services\Matching\RecommendationService;
use App\Support\Profile\ProfileCardPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchController extends ApiController
{
    public function __construct(
        private readonly RecommendationService $recommendations,
        private readonly ProfileCardPresenter $presenter,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user()->ensureProfile();
        $viewer->loadMissing('partnerPreference');

        $recommended = $this->recommendations->recommended($viewer, 20);
        $ctx = $this->presenter->contextFor($viewer, $recommended->pluck('profile.id')->all());

        return $this->ok(
            $recommended->map(fn (array $m) => $this->presenter->card($m['profile'], $viewer, array_merge($ctx, [
                'score' => $m['score'],
                'reasons' => $m['reasons'],
            ])))->all(),
        );
    }
}
