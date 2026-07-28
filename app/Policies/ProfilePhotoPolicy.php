<?php

namespace App\Policies;

use App\Enums\PhotoStatus;
use App\Models\ProfilePhoto;
use App\Models\User;

class ProfilePhotoPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['Super Admin', 'Platform Owner'])) {
            return true;
        }

        return null;
    }

    /**
     * Owner and photo-moderation staff can always view. Other members may view
     * only approved photos, and only when the owner's photo_visibility allows
     * that audience. Fine-grained "connected/premium" gating is layered in by
     * the discovery service in Phase 3.
     */
    public function view(User $user, ProfilePhoto $photo): bool
    {
        if ($this->owns($user, $photo) || $user->can('profiles.moderate_photos') || $user->can('profiles.view')) {
            return true;
        }

        if ($photo->status !== PhotoStatus::Approved) {
            return false;
        }

        $visibility = optional($photo->profile->preferences)->photo_visibility ?? 'members';

        return in_array($visibility, ['everyone', 'members', 'verified'], true);
    }

    public function create(User $user, ProfilePhoto $photo): bool
    {
        return $this->owns($user, $photo);
    }

    public function update(User $user, ProfilePhoto $photo): bool
    {
        return $this->owns($user, $photo);
    }

    public function delete(User $user, ProfilePhoto $photo): bool
    {
        return $this->owns($user, $photo);
    }

    public function moderate(User $user): bool
    {
        return $user->can('profiles.moderate_photos');
    }

    private function owns(User $user, ProfilePhoto $photo): bool
    {
        return $photo->profile !== null && $photo->profile->user_id === $user->id;
    }
}
