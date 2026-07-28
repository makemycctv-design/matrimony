<?php

namespace App\Support\Profile;

use App\Enums\InterestStatus;
use App\Models\Interest;
use App\Models\MemberProfile;
use App\Models\Shortlist;
use App\Services\Matching\ProfileVisibilityService;

/**
 * Serializes candidate profiles for discovery surfaces (search results, match
 * sections, profile detail), applying field-level visibility masking. Viewer
 * context (shortlisted ids, interest map) is passed in to avoid N+1 queries in
 * lists.
 */
class ProfileCardPresenter
{
    public function __construct(private readonly ProfileVisibilityService $visibility) {}

    /**
     * Build viewer-relative context (shortlist + interest state) for a set of
     * target profile ids in a single pass, avoiding N+1 queries in lists.
     *
     * @param  list<int>  $targetIds
     * @return array{shortlisted:list<int>, interests_sent:array<int,string>, interests_received:array<int,string>}
     */
    public function contextFor(MemberProfile $viewer, array $targetIds): array
    {
        if (empty($targetIds)) {
            return ['shortlisted' => [], 'interests_sent' => [], 'interests_received' => []];
        }

        $toValue = fn ($s) => $s instanceof InterestStatus ? $s->value : (string) $s;

        return [
            'shortlisted' => Shortlist::query()
                ->where('member_profile_id', $viewer->id)
                ->whereIn('shortlisted_profile_id', $targetIds)
                ->pluck('shortlisted_profile_id')->all(),
            'interests_sent' => Interest::query()
                ->where('sender_profile_id', $viewer->id)
                ->whereIn('receiver_profile_id', $targetIds)
                ->pluck('status', 'receiver_profile_id')->map($toValue)->all(),
            'interests_received' => Interest::query()
                ->where('receiver_profile_id', $viewer->id)
                ->whereIn('sender_profile_id', $targetIds)
                ->pluck('status', 'sender_profile_id')->map($toValue)->all(),
        ];
    }

    /**
     * @param  array{shortlisted?:list<int>, interests_sent?:array<int,string>, interests_received?:array<int,string>, score?:int, reasons?:list<string>}  $ctx
     */
    public function card(MemberProfile $target, MemberProfile $viewer, array $ctx = []): array
    {
        $canSeePhoto = $this->visibility->canSeeField($viewer, $target, 'photo');
        $primary = $target->relationLoaded('primaryPhoto') ? $target->primaryPhoto : $target->primaryPhoto()->first();
        $photoVisible = $canSeePhoto && $primary && $primary->isApproved();

        return [
            'uuid' => $target->uuid,
            'profile_code' => $target->profile_code,
            'display_name' => $target->display_name,
            'age' => $target->age,
            'height_cm' => $target->height_cm,
            'gender' => $target->gender?->value,
            'religion' => $target->religion?->localizedName(),
            'mother_tongue' => $target->motherTongue?->localizedName(),
            'profession' => $target->profession?->localizedName() ?? $target->profession_detail,
            'education' => $target->education?->localizedName(),
            'city' => $target->city?->localizedName(),
            'state' => $target->state?->localizedName(),
            'is_verified' => $target->is_verified,
            'has_photo' => (bool) $primary,
            'photo_url' => $photoVisible ? $primary->thumbUrl() : null,
            'photo_locked' => (bool) $primary && ! $canSeePhoto,
            'last_active_at' => $target->last_active_at?->diffForHumans(),
            // Viewer-relative flags.
            'is_shortlisted' => in_array($target->id, $ctx['shortlisted'] ?? [], true),
            'interest_sent' => ($ctx['interests_sent'] ?? [])[$target->id] ?? null,
            'interest_received' => ($ctx['interests_received'] ?? [])[$target->id] ?? null,
            // Matching (optional).
            'score' => $ctx['score'] ?? null,
            'reasons' => $ctx['reasons'] ?? null,
        ];
    }

    /** Full profile detail for the public detail page, with masked fields. */
    public function detail(MemberProfile $target, MemberProfile $viewer, array $ctx = []): array
    {
        $connected = $this->visibility->areConnected($viewer, $target);
        $seeContact = $this->visibility->canSeeField($viewer, $target, 'contact');
        $seeIncome = $this->visibility->canSeeField($viewer, $target, 'income');
        $seeHoroscope = $this->visibility->canSeeField($viewer, $target, 'horoscope');
        $seePhoto = $this->visibility->canSeeField($viewer, $target, 'photo');

        $photos = ($target->relationLoaded('photos') ? $target->photos : $target->photos()->approved()->get())
            ->filter(fn ($p) => $p->isApproved())
            ->map(fn ($p) => ['uuid' => $p->uuid, 'url' => $seePhoto ? $p->url() : null, 'thumb_url' => $seePhoto ? $p->thumbUrl() : null, 'locked' => ! $seePhoto])
            ->values()
            ->all();

        return array_merge($this->card($target, $viewer, $ctx), [
            'connected' => $connected,
            'about_me' => $target->about_me,
            'partner_expectations_note' => $target->partner_expectations_note,
            'marital_status' => $target->marital_status,
            'physical_status' => $target->physical_status,
            'complexion' => $target->complexion,
            'employment_type' => $target->employment_type,
            'family' => [
                'type' => $target->family_type,
                'status' => $target->family_status,
                'values' => $target->family_values,
                'details' => $target->family_details,
            ],
            'lifestyle' => [
                'diet' => $target->diet,
                'smoking' => $target->smoking,
                'drinking' => $target->drinking,
                'extra' => $target->lifestyle,
            ],
            'native_place' => $target->native_place,
            'photos' => $photos,
            // Masked-sensitive blocks.
            'income' => $seeIncome ? ['min' => $target->annual_income_min, 'max' => $target->annual_income_max] : null,
            'income_locked' => ! $seeIncome && ($target->annual_income_min || $target->annual_income_max),
            'horoscope' => $seeHoroscope && $target->horoscope_enabled ? [
                'star' => $target->star, 'rasi' => $target->rasi, 'dosham' => $target->dosham,
                'birth_time' => $target->birth_time, 'birth_place' => $target->birth_place,
            ] : null,
            'horoscope_locked' => ! $seeHoroscope && $target->horoscope_enabled,
            'contact' => $seeContact ? [
                'mobile' => $target->user?->fullMobile(),
                'email' => $target->user?->email,
            ] : null,
            'contact_locked' => ! $seeContact,
        ]);
    }
}
