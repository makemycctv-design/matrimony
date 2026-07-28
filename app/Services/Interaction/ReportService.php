<?php

namespace App\Services\Interaction;

use App\Enums\ReportStatus;
use App\Models\MemberProfile;
use App\Models\ProfileReport;
use Illuminate\Validation\ValidationException;

class ReportService
{
    /**
     * File an abuse report. Prevents duplicate open reports for the same pair
     * to avoid spamming the moderation queue.
     */
    public function report(MemberProfile $reporter, MemberProfile $reported, string $reason, ?string $details = null): ProfileReport
    {
        if ($reporter->id === $reported->id) {
            throw ValidationException::withMessages(['report' => 'You cannot report your own profile.']);
        }

        $openExists = ProfileReport::query()
            ->where('reporter_profile_id', $reporter->id)
            ->where('reported_profile_id', $reported->id)
            ->whereIn('status', [ReportStatus::Pending->value, ReportStatus::Reviewing->value])
            ->exists();

        if ($openExists) {
            throw ValidationException::withMessages(['report' => 'You already have an open report against this profile.']);
        }

        $report = new ProfileReport([
            'reporter_profile_id' => $reporter->id,
            'reported_profile_id' => $reported->id,
            'reason' => $reason,
            'details' => $details ? mb_substr($details, 0, 2000) : null,
            'status' => ReportStatus::Pending,
        ]);
        $report->company_id = $reporter->company_id;
        $report->save();

        return $report;
    }
}
