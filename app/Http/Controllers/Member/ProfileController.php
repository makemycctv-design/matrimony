<?php

namespace App\Http\Controllers\Member;

use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileSectionRequest;
use App\Services\Profile\ProfileOptionsService;
use App\Services\Profile\ProfileService;
use App\Services\Profile\VerificationService;
use App\Support\Profile\ProfilePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The member's own profile: a multi-step wizard for editing every section,
 * plus submitting the completed profile for verification.
 */
class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profiles,
        private readonly VerificationService $verification,
    ) {}

    public function edit(Request $request, ProfileOptionsService $options, ProfilePresenter $presenter): Response
    {
        $profile = $request->user()->ensureProfile();
        $this->authorize('update', $profile);

        return Inertia::render('member/profile/edit', [
            'profile' => $presenter->forOwner($profile),
            'options' => $options->all(),
            'canSubmit' => $this->profiles->meetsSubmissionRequirements($profile),
            'documentTypes' => DocumentType::options(),
        ]);
    }

    public function updateSection(UpdateProfileSectionRequest $request, string $section): RedirectResponse
    {
        abort_unless(in_array($section, UpdateProfileSectionRequest::SECTIONS, true), 404);

        $profile = $request->user()->ensureProfile();
        $this->authorize('update', $profile);

        $this->profiles->updateSection($profile, $request->validated());

        return back()->with('success', ucfirst($section).' details saved.');
    }

    public function submit(Request $request): RedirectResponse
    {
        $profile = $request->user()->ensureProfile();
        $this->authorize('update', $profile);

        if (! $this->profiles->meetsSubmissionRequirements($profile)) {
            throw ValidationException::withMessages([
                'profile' => 'Please complete the required sections (at least 60%) before submitting for verification.',
            ]);
        }

        $this->verification->submitForReview($profile);

        return back()->with('success', 'Your profile has been submitted for verification.');
    }
}
