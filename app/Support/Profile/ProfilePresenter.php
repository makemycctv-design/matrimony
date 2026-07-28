<?php

namespace App\Support\Profile;

use App\Models\MemberProfile;
use App\Models\ProfileDocument;
use App\Models\ProfilePhoto;
use App\Models\ProfileVerification;

/**
 * Serializes a MemberProfile into the shapes the frontend needs. Keeps
 * controllers thin and ensures sensitive fields (raw document numbers, private
 * paths) never leak — documents expose only masked hints + short-lived signed
 * URLs.
 */
class ProfilePresenter
{
    /** Full editable payload for the profile owner's wizard. */
    public function forOwner(MemberProfile $profile): array
    {
        $profile->loadMissing(['photos', 'documents', 'verifications.actor:id,name', 'preferences']);

        return [
            'uuid' => $profile->uuid,
            'profile_code' => $profile->profile_code,
            'status' => $profile->status?->value,
            'is_verified' => $profile->is_verified,
            'rejection_reason' => $profile->rejection_reason,
            'completion_percentage' => $profile->completion_percentage,
            'fields' => $this->fields($profile),
            'photos' => $profile->photos->map(fn (ProfilePhoto $p) => $this->photo($p))->all(),
            'documents' => $profile->documents->map(fn (ProfileDocument $d) => $this->document($d))->all(),
            'verifications' => $profile->verifications->take(20)->map(fn (ProfileVerification $v) => [
                'scope' => $v->scope,
                'action' => $v->action,
                'reason' => $v->reason,
                'actor' => $v->actor?->name,
                'at' => $v->created_at?->toIso8601String(),
            ])->all(),
            'preferences' => $profile->preferences,
        ];
    }

    /** Admin review payload (adds member/account context). */
    public function forAdmin(MemberProfile $profile): array
    {
        $profile->loadMissing(['user:id,uuid,name,email,country_code,mobile,status,created_at']);

        return array_merge($this->forOwner($profile), [
            'age' => $profile->age,
            'user' => [
                'uuid' => $profile->user?->uuid,
                'name' => $profile->user?->name,
                'email' => $profile->user?->email,
                'mobile' => $profile->user?->fullMobile(),
                'status' => $profile->user?->status?->value,
                'joined_at' => $profile->user?->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * The raw column values used to hydrate the wizard form.
     *
     * @return array<string, mixed>
     */
    private function fields(MemberProfile $p): array
    {
        return [
            'first_name' => $p->first_name,
            'last_name' => $p->last_name,
            'name_display' => $p->name_display,
            'date_of_birth' => $p->date_of_birth?->toDateString(),
            'gender' => $p->gender?->value,
            'marital_status' => $p->marital_status,
            'height_cm' => $p->height_cm,
            'weight_kg' => $p->weight_kg,
            'physical_status' => $p->physical_status,
            'body_type' => $p->body_type,
            'complexion' => $p->complexion,
            'religion_id' => $p->religion_id,
            'caste_id' => $p->caste_id,
            'sub_caste_id' => $p->sub_caste_id,
            'mother_tongue_id' => $p->mother_tongue_id,
            'gothra' => $p->gothra,
            'education_id' => $p->education_id,
            'education_detail' => $p->education_detail,
            'profession_id' => $p->profession_id,
            'profession_detail' => $p->profession_detail,
            'company_name' => $p->company_name,
            'employment_type' => $p->employment_type,
            'annual_income_min' => $p->annual_income_min,
            'annual_income_max' => $p->annual_income_max,
            'country_id' => $p->country_id,
            'state_id' => $p->state_id,
            'district_id' => $p->district_id,
            'city_id' => $p->city_id,
            'native_place' => $p->native_place,
            'residency_status' => $p->residency_status,
            'family_type' => $p->family_type,
            'family_status' => $p->family_status,
            'family_values' => $p->family_values,
            'father_occupation' => $p->father_occupation,
            'mother_occupation' => $p->mother_occupation,
            'brothers' => $p->brothers,
            'brothers_married' => $p->brothers_married,
            'sisters' => $p->sisters,
            'sisters_married' => $p->sisters_married,
            'family_details' => $p->family_details,
            'diet' => $p->diet,
            'smoking' => $p->smoking,
            'drinking' => $p->drinking,
            'lifestyle' => $p->lifestyle ?? [],
            'horoscope_enabled' => $p->horoscope_enabled,
            'birth_time' => $p->birth_time,
            'birth_place' => $p->birth_place,
            'star' => $p->star,
            'rasi' => $p->rasi,
            'dosham' => $p->dosham,
            'about_me' => $p->about_me,
            'partner_expectations_note' => $p->partner_expectations_note,
        ];
    }

    public function photo(ProfilePhoto $photo): array
    {
        return [
            'uuid' => $photo->uuid,
            'url' => $photo->url(),
            'thumb_url' => $photo->thumbUrl(),
            'is_primary' => $photo->is_primary,
            'status' => $photo->status?->value,
            'moderation_reason' => $photo->moderation_reason,
            'sort_order' => $photo->sort_order,
        ];
    }

    public function document(ProfileDocument $doc): array
    {
        return [
            'uuid' => $doc->uuid,
            'type' => $doc->type?->value,
            'type_label' => $doc->type?->label(),
            'last4' => $doc->document_last4,
            'status' => $doc->status?->value,
            'rejection_reason' => $doc->rejection_reason,
            'original_name' => $doc->original_name,
            'mime_type' => $doc->mime_type,
            // Short-lived signed URL; never the raw path.
            'url' => $doc->signedUrl(),
            'uploaded_at' => $doc->created_at?->toIso8601String(),
        ];
    }
}
