<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Search\PartnerPreferenceRequest;
use App\Services\Matching\MatchingSettingsService;
use App\Services\Profile\ProfileOptionsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PartnerPreferenceController extends Controller
{
    public function edit(Request $request, ProfileOptionsService $options, MatchingSettingsService $settings): Response
    {
        $profile = $request->user()->ensureProfile();
        $preference = $profile->partnerPreference()->firstOrCreate([
            'company_id' => $profile->company_id,
        ], [
            // Sensible default: seek the opposite gender.
            'preferred_gender' => $profile->gender?->opposite()->value,
        ]);

        return Inertia::render('member/partner-preferences', [
            'preference' => $preference,
            'options' => $options->all(),
            'weightFactors' => array_keys(MatchingSettingsService::DEFAULT_WEIGHTS),
            'defaultWeights' => $settings->weights(),
        ]);
    }

    public function update(PartnerPreferenceRequest $request): RedirectResponse
    {
        $profile = $request->user()->ensureProfile();

        $preference = $profile->partnerPreference()->firstOrNew([]);
        $preference->company_id = $profile->company_id;
        $preference->member_profile_id = $profile->id;
        $preference->fill($request->validated());
        $preference->save();

        return back()->with('success', 'Partner preferences updated. Your recommendations will refresh.');
    }
}
