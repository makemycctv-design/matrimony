<?php

namespace App\Http\Controllers\Member;

use App\Enums\ReportReason;
use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use App\Services\Interaction\ProfileViewService;
use App\Services\Matching\MatchScoringService;
use App\Services\Matching\ProfileVisibilityService;
use App\Support\Profile\ProfileCardPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileDetailController extends Controller
{
    public function __construct(
        private readonly ProfileVisibilityService $visibility,
        private readonly ProfileCardPresenter $presenter,
        private readonly MatchScoringService $scoring,
        private readonly ProfileViewService $views,
    ) {}

    public function show(Request $request, MemberProfile $profile): Response
    {
        $viewer = $request->user()->ensureProfile();

        // Do not reveal the existence of profiles the viewer may not see.
        abort_unless($this->visibility->canView($viewer, $profile), 404);

        $profile->loadMissing([
            'religion', 'caste', 'subCaste', 'motherTongue', 'profession', 'education',
            'country', 'state', 'district', 'city', 'photos', 'preferences', 'user',
        ]);

        if ($viewer->id !== $profile->id) {
            $this->views->record($viewer, $profile);
        }

        $ctx = $this->presenter->contextFor($viewer, [$profile->id]);
        $score = $this->scoring->score($viewer, $profile);

        return Inertia::render('member/profile-detail', [
            'profile' => $this->presenter->detail($profile, $viewer, array_merge($ctx, [
                'score' => $score['score'],
                'reasons' => $score['reasons'],
            ])),
            'reportReasons' => ReportReason::options(),
            'isSelf' => $viewer->id === $profile->id,
        ]);
    }
}
