<?php

namespace App\Services\Interaction;

use App\Models\MemberProfile;
use App\Models\ProfileView;
use Illuminate\Support\Carbon;

class ProfileViewService
{
    /** Record (or bump) a profile view. Self-views are ignored. */
    public function record(MemberProfile $viewer, MemberProfile $viewed): void
    {
        if ($viewer->id === $viewed->id) {
            return;
        }

        $view = ProfileView::firstOrNew([
            'viewer_profile_id' => $viewer->id,
            'viewed_profile_id' => $viewed->id,
        ]);

        $view->company_id = $viewer->company_id;
        $view->view_count = ($view->view_count ?? 0) + 1;
        $view->last_viewed_at = Carbon::now();
        $view->save();
    }

    public function viewCountFor(MemberProfile $profile): int
    {
        return (int) ProfileView::where('viewed_profile_id', $profile->id)->sum('view_count');
    }
}
