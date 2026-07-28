<?php

namespace App\Services\Profile;

use App\Enums\DocumentStatus;
use App\Enums\PhotoStatus;
use App\Enums\ProfileStatus;
use App\Events\Profile\ProfilePhotoModerated;
use App\Events\Profile\ProfileRejected;
use App\Events\Profile\ProfileSubmittedForReview;
use App\Events\Profile\ProfileVerified;
use App\Models\MemberProfile;
use App\Models\ProfileDocument;
use App\Models\ProfilePhoto;
use App\Models\ProfileVerification;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Central authority for the verification lifecycle of profiles, photos, and
 * KYC documents. Every decision is (a) applied transactionally, (b) recorded in
 * the profile_verifications history, (c) written to the sensitive audit log,
 * and (d) broadcast via a domain event that drives member notifications.
 */
class VerificationService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** Member submits their profile for staff review. */
    public function submitForReview(MemberProfile $profile): void
    {
        DB::transaction(function () use ($profile): void {
            $wasRejected = $profile->status === ProfileStatus::Rejected;

            $profile->forceFill([
                'status' => ProfileStatus::Submitted,
                'rejection_reason' => null,
            ])->save();

            $this->record($profile, 'profile', $wasRejected ? 'resubmitted' : 'submitted');
        });

        ProfileSubmittedForReview::dispatch($profile);
    }

    /** Staff approve & verify a profile. */
    public function approve(MemberProfile $profile, ?string $notes = null): void
    {
        DB::transaction(function () use ($profile, $notes): void {
            $profile->forceFill([
                'status' => ProfileStatus::Verified,
                'is_verified' => true,
                'verified_at' => Carbon::now(),
                'rejection_reason' => null,
            ])->save();

            $this->record($profile, 'profile', 'approved', $notes);
            $this->audit->log('profile.verified', $profile, "Profile {$profile->profile_code} verified", null, ['notes' => $notes]);
        });

        ProfileVerified::dispatch($profile);
    }

    /** Staff reject a profile with a member-visible reason. */
    public function reject(MemberProfile $profile, string $reason): void
    {
        DB::transaction(function () use ($profile, $reason): void {
            $profile->forceFill([
                'status' => ProfileStatus::Rejected,
                'is_verified' => false,
                'rejection_reason' => $reason,
            ])->save();

            $this->record($profile, 'profile', 'rejected', $reason);
            $this->audit->log('profile.rejected', $profile, "Profile {$profile->profile_code} rejected", null, ['reason' => $reason]);
        });

        ProfileRejected::dispatch($profile, $reason);
    }

    /** Staff suspend a profile (removes it from discovery). */
    public function suspend(MemberProfile $profile, string $reason): void
    {
        DB::transaction(function () use ($profile, $reason): void {
            $profile->forceFill([
                'status' => ProfileStatus::Suspended,
                'is_verified' => false,
            ])->save();

            $this->record($profile, 'profile', 'suspended', $reason);
            $this->audit->log('profile.suspended', $profile, "Profile {$profile->profile_code} suspended", null, ['reason' => $reason]);
        });
    }

    public function moderatePhoto(ProfilePhoto $photo, bool $approve, ?string $reason = null): void
    {
        DB::transaction(function () use ($photo, $approve, $reason): void {
            $photo->forceFill([
                'status' => $approve ? PhotoStatus::Approved : PhotoStatus::Rejected,
                'moderation_reason' => $approve ? null : $reason,
                'moderated_by' => Auth::id(),
                'moderated_at' => Carbon::now(),
            ])->save();

            if ($approve && $photo->is_primary) {
                $photo->profile?->forceFill(['is_photo_verified' => true])->save();
            }

            $this->record($photo->profile, 'photo', $approve ? 'approved' : 'rejected', $reason, ['photo_id' => $photo->uuid]);
            $this->audit->log(
                $approve ? 'photo.approved' : 'photo.rejected',
                $photo,
                "Photo {$photo->uuid} ".($approve ? 'approved' : 'rejected'),
            );
        });

        ProfilePhotoModerated::dispatch($photo, $approve, $reason);
    }

    public function reviewDocument(ProfileDocument $document, bool $approve, ?string $reason = null, ?string $notes = null): void
    {
        DB::transaction(function () use ($document, $approve, $reason, $notes): void {
            $document->forceFill([
                'status' => $approve ? DocumentStatus::Approved : DocumentStatus::Rejected,
                'rejection_reason' => $approve ? null : $reason,
                'review_notes' => $notes,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => Carbon::now(),
            ])->save();

            $this->record($document->profile, 'document', $approve ? 'approved' : 'rejected', $reason, [
                'document_id' => $document->uuid,
                'document_type' => $document->type->value,
            ]);
            // Never log the document number or file contents.
            $this->audit->log(
                $approve ? 'document.approved' : 'document.rejected',
                $document,
                "Document {$document->uuid} ({$document->type->value}) ".($approve ? 'approved' : 'rejected'),
            );
        });
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function record(?MemberProfile $profile, string $scope, string $action, ?string $reason = null, ?array $meta = null): void
    {
        if ($profile === null) {
            return;
        }

        $record = new ProfileVerification([
            'member_profile_id' => $profile->id,
            'actor_id' => Auth::id(),
            'scope' => $scope,
            'action' => $action,
            'reason' => $reason,
            'meta' => $meta,
        ]);
        $record->company_id = $profile->company_id;
        $record->save();
    }
}
