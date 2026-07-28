<?php

namespace App\Services\Profile;

use App\Enums\ProfileStatus;
use App\Models\MemberProfile;

/**
 * Applies validated profile-section updates and keeps the completion score in
 * sync. Section data is already validated by the wizard FormRequests, and
 * company_id / status / verification flags remain guarded on the model.
 */
class ProfileService
{
    public function __construct(private readonly ProfileCompletionCalculator $completion) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSection(MemberProfile $profile, array $data): MemberProfile
    {
        $profile->fill($data);
        $profile->save();

        $this->completion->refresh($profile->refresh());

        return $profile;
    }

    /**
     * Determine whether a profile has the minimum information required before
     * it can be submitted for verification.
     */
    public function meetsSubmissionRequirements(MemberProfile $profile): bool
    {
        return filled($profile->first_name)
            && filled($profile->date_of_birth)
            && filled($profile->gender)
            && filled($profile->marital_status)
            && $profile->completion_percentage >= 60;
    }

    public function isEditable(MemberProfile $profile): bool
    {
        return ! in_array($profile->status, [ProfileStatus::Suspended], true);
    }
}
