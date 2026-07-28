<?php

namespace App\Services\Profile;

use App\Enums\PhotoStatus;
use App\Jobs\ProcessProfilePhoto;
use App\Models\MemberProfile;
use App\Models\ProfilePhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Handles the member photo gallery: secure upload to the private disk, primary
 * selection, ordering, and deletion. Uploaded photos start in "pending" and
 * only become visible once approved by moderation. Thumbnail generation is
 * offloaded to a queued job.
 */
class PhotoService
{
    public const DISK = 'private';

    public const MAX_PHOTOS = 10;

    public function upload(MemberProfile $profile, UploadedFile $file): ProfilePhoto
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $name = Str::ulid().'.'.$extension;
        $directory = "profiles/{$profile->id}/photos";

        $path = $file->storeAs($directory, $name, self::DISK);

        $isFirst = ! $profile->photos()->exists();

        $photo = new ProfilePhoto([
            'member_profile_id' => $profile->id,
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => Str::limit($file->getClientOriginalName(), 190, ''),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'is_primary' => $isFirst,
            'sort_order' => (int) $profile->photos()->max('sort_order') + 1,
        ]);
        $photo->company_id = $profile->company_id;
        $photo->save();

        // Generate a thumbnail + capture dimensions off the request path.
        ProcessProfilePhoto::dispatch($photo->id);

        return $photo;
    }

    public function setPrimary(ProfilePhoto $photo): void
    {
        DB::transaction(function () use ($photo): void {
            ProfilePhoto::where('member_profile_id', $photo->member_profile_id)
                ->where('id', '!=', $photo->id)
                ->update(['is_primary' => false]);

            $photo->forceFill(['is_primary' => true])->save();
        });
    }

    public function delete(ProfilePhoto $photo): void
    {
        $wasPrimary = $photo->is_primary;
        $profileId = $photo->member_profile_id;

        $photo->forceDelete();

        // Promote the next photo to primary if we removed the primary one.
        if ($wasPrimary) {
            $next = ProfilePhoto::where('member_profile_id', $profileId)->orderBy('sort_order')->first();
            $next?->forceFill(['is_primary' => true])->save();
        }
    }

    /**
     * @param  list<string>  $orderedUuids
     */
    public function reorder(MemberProfile $profile, array $orderedUuids): void
    {
        DB::transaction(function () use ($profile, $orderedUuids): void {
            foreach ($orderedUuids as $index => $uuid) {
                $profile->photos()->where('uuid', $uuid)->update(['sort_order' => $index]);
            }
        });
    }

    public function canUploadMore(MemberProfile $profile): bool
    {
        return $profile->photos()->count() < self::MAX_PHOTOS;
    }

    public function pendingModerationCount(): int
    {
        return ProfilePhoto::where('status', PhotoStatus::Pending->value)->count();
    }
}
