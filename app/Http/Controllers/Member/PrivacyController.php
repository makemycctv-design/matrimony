<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\PrivacyUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyController extends Controller
{
    public function edit(Request $request): Response
    {
        $profile = $request->user()->ensureProfile();
        $profile->loadMissing('preferences');

        return Inertia::render('member/privacy', [
            'preferences' => $profile->preferences,
        ]);
    }

    public function update(PrivacyUpdateRequest $request): RedirectResponse
    {
        $profile = $request->user()->ensureProfile();
        $this->authorize('update', $profile);

        $profile->preferences()->update($request->validated());

        return back()->with('success', 'Your privacy settings have been updated.');
    }
}
