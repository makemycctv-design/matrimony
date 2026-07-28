<?php

namespace App\Policies;

use App\Models\MemberProfile;
use App\Models\User;

class MemberProfilePolicy
{
    /**
     * Super Admin / Platform Owner bypass all checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['Super Admin', 'Platform Owner'])) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('profiles.view') || $user->hasRole('Registered Member');
    }

    /**
     * A member can always view their own profile. Staff with the permission can
     * view any. Otherwise visibility depends on the target's privacy settings
     * (finer-grained field masking happens in the presenter layer).
     */
    public function view(User $user, MemberProfile $profile): bool
    {
        if ($user->id === $profile->user_id) {
            return true;
        }

        if ($user->can('profiles.view')) {
            return true;
        }

        // Blocked or hidden profiles are excluded here; detailed rules are
        // applied by the discovery/visibility service in later phases.
        return $profile->isDiscoverable()
            && (bool) optional($profile->preferences)->appear_in_search !== false;
    }

    public function update(User $user, MemberProfile $profile): bool
    {
        return $user->id === $profile->user_id || $user->can('users.manage');
    }

    public function delete(User $user, MemberProfile $profile): bool
    {
        return $user->id === $profile->user_id || $user->can('profiles.delete');
    }

    public function approve(User $user): bool
    {
        return $user->can('profiles.approve');
    }

    public function reject(User $user): bool
    {
        return $user->can('profiles.reject');
    }

    public function suspend(User $user): bool
    {
        return $user->can('profiles.suspend');
    }

    public function verifyDocuments(User $user): bool
    {
        return $user->can('profiles.verify_documents');
    }

    public function moderatePhotos(User $user): bool
    {
        return $user->can('profiles.moderate_photos');
    }
}
