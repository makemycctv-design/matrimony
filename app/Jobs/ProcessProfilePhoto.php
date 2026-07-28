<?php

namespace App\Jobs;

use App\Models\ProfilePhoto;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Generates a downscaled thumbnail and records image dimensions for a photo.
 * Uses the GD extension directly (no third-party package). Degrades gracefully
 * — if GD or the source is unusable, the original is served as-is.
 */
class ProcessProfilePhoto implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    private const THUMB_WIDTH = 480;

    public function __construct(public int $photoId) {}

    public function handle(): void
    {
        $photo = ProfilePhoto::find($this->photoId);

        if ($photo === null) {
            return;
        }

        $disk = Storage::disk($photo->disk);

        if (! $disk->exists($photo->path)) {
            return;
        }

        $binary = $disk->get($photo->path);
        $image = @imagecreatefromstring($binary);

        if ($image === false || ! function_exists('imagecreatetruecolor')) {
            return; // Non-image or GD unavailable — serve the original.
        }

        $width = imagesx($image);
        $height = imagesy($image);

        $updates = ['width' => $width, 'height' => $height];

        if ($width > self::THUMB_WIDTH) {
            $ratio = self::THUMB_WIDTH / $width;
            $thumbW = self::THUMB_WIDTH;
            $thumbH = (int) round($height * $ratio);

            $thumb = imagecreatetruecolor($thumbW, $thumbH);
            imagecopyresampled($thumb, $image, 0, 0, 0, 0, $thumbW, $thumbH, $width, $height);

            ob_start();
            imagejpeg($thumb, null, 82);
            $thumbData = (string) ob_get_clean();
            imagedestroy($thumb);

            $thumbPath = preg_replace('/(\.[a-z0-9]+)$/i', '', $photo->path).'_thumb_'.Str::random(6).'.jpg';
            $disk->put($thumbPath, $thumbData, 'private');

            $updates['thumbnail_path'] = $thumbPath;
        }

        imagedestroy($image);

        $photo->forceFill($updates)->save();
    }
}
