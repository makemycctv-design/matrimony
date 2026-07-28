<?php

namespace App\Http\Controllers;

use App\Models\ProfileDocument;
use App\Models\ProfilePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves sensitive member media from the private disk. Every request is
 * authorized by policy; documents additionally require a valid signed URL.
 * Files are never exposed through a public path.
 */
class MediaController extends Controller
{
    /** Stream a profile photo (optionally the thumbnail variant). */
    public function photo(Request $request, ProfilePhoto $photo, ?string $variant = null): StreamedResponse
    {
        $this->authorize('view', $photo);

        $path = $variant === 'thumb' && $photo->thumbnail_path
            ? $photo->thumbnail_path
            : $photo->path;

        return $this->stream($photo->disk, $path, $photo->mime_type);
    }

    /** Stream a KYC document — reached only via a short-lived signed URL. */
    public function document(Request $request, ProfileDocument $document): StreamedResponse
    {
        abort_unless($request->hasValidSignature(), 403);

        $this->authorize('view', $document);

        return $this->stream($document->disk, $document->path, $document->mime_type, download: false);
    }

    private function stream(string $disk, string $path, ?string $mime, bool $download = false): StreamedResponse
    {
        $storage = Storage::disk($disk);

        abort_unless($storage->exists($path), 404);

        $headers = [
            'Content-Type' => $mime ?? 'application/octet-stream',
            'Cache-Control' => 'private, max-age=300, no-store',
        ];

        return $storage->response($path, null, $headers, $download ? 'attachment' : 'inline');
    }
}
