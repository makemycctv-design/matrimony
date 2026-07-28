<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\DocumentUploadRequest;
use App\Models\ProfileDocument;
use App\Services\Profile\PhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function store(DocumentUploadRequest $request): RedirectResponse
    {
        $profile = $request->user()->ensureProfile();
        $this->authorize('update', $profile);

        $file = $request->file('document');
        $name = Str::ulid().'.'.strtolower($file->getClientOriginalExtension() ?: 'bin');
        $path = $file->storeAs("profiles/{$profile->id}/documents", $name, PhotoService::DISK);

        $number = $request->input('document_number');

        $document = new ProfileDocument([
            'member_profile_id' => $profile->id,
            'type' => $request->validated('type'),
            'document_number' => $number, // encrypted via cast
            'document_last4' => $number ? Str::substr(preg_replace('/\s+/', '', $number), -4) : null,
            'disk' => PhotoService::DISK,
            'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 190, ''),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
        $document->company_id = $profile->company_id;
        $document->save();

        return back()->with('success', 'Document uploaded for verification.');
    }

    public function destroy(ProfileDocument $document): RedirectResponse
    {
        $this->authorize('delete', $document);
        $document->forceDelete();

        return back()->with('success', 'Document removed.');
    }
}
