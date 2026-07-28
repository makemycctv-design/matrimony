<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use App\Services\Profile\ProfileCompletionCalculator;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, ProfileCompletionCalculator $completion): Response
    {
        $user = $request->user();
        $profile = $user->profile;

        if ($profile === null) {
            // company_id is guarded (tenant integrity); set it explicitly.
            $profile = new MemberProfile(['first_name' => explode(' ', $user->name)[0]]);
            $profile->company_id = $user->company_id;
            $user->profile()->save($profile);
        }

        $completionResult = $completion->calculate($profile);
        $completion->refresh($profile);

        return Inertia::render('member/dashboard', [
            'profile' => [
                'code' => $profile->profile_code,
                'display_name' => $profile->display_name,
                'status' => $profile->status?->value,
                'is_verified' => $profile->is_verified,
                'completion' => $completionResult['percentage'],
                'suggestions' => $completionResult['suggestions'],
            ],
            'verification' => [
                'email_verified' => $user->hasVerifiedEmail(),
                'mobile_verified' => $user->isMobileVerified(),
            ],
            // Discovery sections are populated by the matching engine in Phase 3.
            'sections' => [
                'recommended' => [],
                'new_matches' => [],
                'near_you' => [],
                'recently_joined' => $this->recentlyJoined($profile),
            ],
            'activity' => [
                'interests_received' => 0,
                'interests_sent' => 0,
                'shortlisted' => 0,
                'profile_views' => 0,
            ],
        ]);
    }

    /**
     * A lightweight "recently joined" strip so the dashboard is not empty
     * before the full matching engine ships. Respects discoverability + gender.
     *
     * @return list<array<string, mixed>>
     */
    private function recentlyJoined(MemberProfile $profile): array
    {
        $query = MemberProfile::query()
            ->where('id', '!=', $profile->id)
            ->whereIn('status', ['verified', 'submitted', 'under_review'])
            ->latest('id')
            ->limit(8);

        if ($profile->gender !== null) {
            $query->where('gender', '!=', $profile->gender->value);
        }

        return $query->get()->map(fn (MemberProfile $p) => [
            'code' => $p->profile_code,
            'display_name' => $p->display_name,
            'age' => $p->age,
            'is_verified' => $p->is_verified,
        ])->all();
    }
}
