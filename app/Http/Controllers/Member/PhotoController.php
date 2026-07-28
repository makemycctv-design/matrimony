<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\PhotoUploadRequest;
use App\Models\ProfilePhoto;
use App\Services\Profile\PhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PhotoController extends Controller
{
    public function __construct(private readonly PhotoService $photos) {}

    public function store(PhotoUploadRequest $request): RedirectResponse
    {
        $profile = $request->user()->ensureProfile();
        $this->authorize('update', $profile);

        if (! $this->photos->canUploadMore($profile)) {
            throw ValidationException::withMessages([
                'photo' => 'You have reached the maximum number of photos ('.PhotoService::MAX_PHOTOS.').',
            ]);
        }

        $this->photos->upload($profile, $request->file('photo'));

        return back()->with('success', 'Photo uploaded. It will be visible once approved.');
    }

    public function setPrimary(ProfilePhoto $photo): RedirectResponse
    {
        $this->authorize('update', $photo);
        $this->photos->setPrimary($photo);

        return back()->with('success', 'Primary photo updated.');
    }

    public function destroy(ProfilePhoto $photo): RedirectResponse
    {
        $this->authorize('delete', $photo);
        $this->photos->delete($photo);

        return back()->with('success', 'Photo removed.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['string'],
        ]);

        $profile = $request->user()->ensureProfile();
        $this->authorize('update', $profile);

        $this->photos->reorder($profile, $validated['order']);

        return back();
    }
}
