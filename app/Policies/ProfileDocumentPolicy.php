<?php

namespace App\Policies;

use App\Enums\DocumentStatus;
use App\Models\ProfileDocument;
use App\Models\User;

class ProfileDocumentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['Super Admin', 'Platform Owner'])) {
            return true;
        }

        return null;
    }

    /** Only the owner or verification staff may ever access a KYC document. */
    public function view(User $user, ProfileDocument $document): bool
    {
        return $this->owns($user, $document) || $user->can('profiles.verify_documents');
    }

    public function delete(User $user, ProfileDocument $document): bool
    {
        // Owners can remove a document only while it is still pending review.
        if ($this->owns($user, $document)) {
            return $document->status === DocumentStatus::Pending;
        }

        return $user->can('profiles.verify_documents');
    }

    public function review(User $user): bool
    {
        return $user->can('profiles.verify_documents');
    }

    private function owns(User $user, ProfileDocument $document): bool
    {
        return $document->profile !== null && $document->profile->user_id === $user->id;
    }
}
