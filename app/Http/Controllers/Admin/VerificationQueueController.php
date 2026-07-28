<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentStatus;
use App\Enums\PhotoStatus;
use App\Http\Controllers\Controller;
use App\Models\ProfileDocument;
use App\Models\ProfilePhoto;
use App\Services\Profile\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Staff review queue for pending photos and KYC documents. Document files are
 * only ever exposed via short-lived signed URLs.
 */
class VerificationQueueController extends Controller
{
    public function __construct(private readonly VerificationService $verification) {}

    public function index(Request $request): Response
    {
        $this->authorize('moderate', ProfilePhoto::class);

        $photos = ProfilePhoto::query()
            ->where('status', PhotoStatus::Pending->value)
            ->with('profile:id,uuid,profile_code,first_name')
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn (ProfilePhoto $p) => [
                'uuid' => $p->uuid,
                'url' => $p->url(),
                'thumb_url' => $p->thumbUrl(),
                'profile_code' => $p->profile?->profile_code,
                'uploaded_at' => $p->created_at?->toIso8601String(),
            ]);

        $documents = collect();
        if ($request->user()->can('profiles.verify_documents')) {
            $documents = ProfileDocument::query()
                ->where('status', DocumentStatus::Pending->value)
                ->with('profile:id,uuid,profile_code,first_name')
                ->latest('id')
                ->limit(50)
                ->get()
                ->map(fn (ProfileDocument $d) => [
                    'uuid' => $d->uuid,
                    'type' => $d->type?->value,
                    'type_label' => $d->type?->label(),
                    'last4' => $d->document_last4,
                    'url' => $d->signedUrl(),
                    'mime_type' => $d->mime_type,
                    'profile_code' => $d->profile?->profile_code,
                    'uploaded_at' => $d->created_at?->toIso8601String(),
                ]);
        }

        return Inertia::render('admin/verifications/index', [
            'photos' => $photos,
            'documents' => $documents,
        ]);
    }

    public function moderatePhoto(Request $request, ProfilePhoto $photo): RedirectResponse
    {
        $this->authorize('moderate', $photo);

        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'reason' => ['nullable', 'required_if:decision,reject', 'string', 'max:500'],
        ]);

        $this->verification->moderatePhoto($photo, $validated['decision'] === 'approve', $validated['reason'] ?? null);

        return back()->with('success', 'Photo moderated.');
    }

    public function reviewDocument(Request $request, ProfileDocument $document): RedirectResponse
    {
        $this->authorize('review', $document);

        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'reason' => ['nullable', 'required_if:decision,reject', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->verification->reviewDocument(
            $document,
            $validated['decision'] === 'approve',
            $validated['reason'] ?? null,
            $validated['notes'] ?? null,
        );

        return back()->with('success', 'Document reviewed.');
    }
}
