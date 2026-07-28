<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Profile\UpdateProfileSectionRequest;
use App\Services\Profile\ProfileService;
use App\Support\Profile\ProfilePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends ApiController
{
    public function __construct(private readonly ProfileService $profiles) {}

    public function show(Request $request, ProfilePresenter $presenter): JsonResponse
    {
        $profile = $request->user()->ensureProfile();

        return $this->ok($presenter->forOwner($profile));
    }

    public function updateSection(UpdateProfileSectionRequest $request, string $section): JsonResponse
    {
        abort_unless(in_array($section, UpdateProfileSectionRequest::SECTIONS, true), 404);

        $profile = $request->user()->ensureProfile();
        $this->profiles->updateSection($profile, $request->validated());

        return $this->message(ucfirst($section).' section updated.');
    }
}
