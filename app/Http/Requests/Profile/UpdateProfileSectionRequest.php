<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a single step of the profile wizard. The step is supplied as the
 * {section} route parameter; each section maps to a discrete set of rules so
 * the same endpoint powers the whole wizard while keeping validation tight.
 */
class UpdateProfileSectionRequest extends FormRequest
{
    public const SECTIONS = [
        'basic', 'community', 'career', 'location', 'family', 'lifestyle', 'horoscope', 'about',
    ];

    public function authorize(): bool
    {
        return true; // Ownership is enforced by the controller policy.
    }

    public function section(): string
    {
        return (string) $this->route('section');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return match ($this->section()) {
            'basic' => [
                'first_name' => ['required', 'string', 'max:100'],
                'last_name' => ['nullable', 'string', 'max:100'],
                'name_display' => ['required', Rule::in(['full', 'first_only', 'initials', 'hidden'])],
                'date_of_birth' => ['required', 'date', 'before:-18 years', 'after:-90 years'],
                'gender' => ['required', Rule::in(['male', 'female', 'other'])],
                'marital_status' => ['required', Rule::in(['never_married', 'divorced', 'widowed', 'separated', 'annulled'])],
                'height_cm' => ['nullable', 'integer', 'min:120', 'max:230'],
                'weight_kg' => ['nullable', 'integer', 'min:30', 'max:200'],
                'physical_status' => ['nullable', Rule::in(['none', 'physically_challenged'])],
                'body_type' => ['nullable', 'string', 'max:30'],
                'complexion' => ['nullable', 'string', 'max:30'],
            ],
            'community' => [
                'religion_id' => ['nullable', Rule::exists('religions', 'id')],
                'caste_id' => ['nullable', Rule::exists('castes', 'id')],
                'sub_caste_id' => ['nullable', Rule::exists('sub_castes', 'id')],
                'mother_tongue_id' => ['nullable', Rule::exists('mother_tongues', 'id')],
                'gothra' => ['nullable', 'string', 'max:100'],
            ],
            'career' => [
                'education_id' => ['nullable', Rule::exists('educations', 'id')],
                'education_detail' => ['nullable', 'string', 'max:150'],
                'profession_id' => ['nullable', Rule::exists('professions', 'id')],
                'profession_detail' => ['nullable', 'string', 'max:150'],
                'company_name' => ['nullable', 'string', 'max:150'],
                'employment_type' => ['nullable', Rule::in(['government', 'private', 'business', 'self_employed', 'not_working'])],
                'annual_income_min' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
                'annual_income_max' => ['nullable', 'integer', 'gte:annual_income_min', 'max:1000000000'],
            ],
            'location' => [
                'country_id' => ['nullable', Rule::exists('locations', 'id')],
                'state_id' => ['nullable', Rule::exists('locations', 'id')],
                'district_id' => ['nullable', Rule::exists('locations', 'id')],
                'city_id' => ['nullable', Rule::exists('locations', 'id')],
                'native_place' => ['nullable', 'string', 'max:120'],
                'residency_status' => ['nullable', 'string', 'max:30'],
            ],
            'family' => [
                'family_type' => ['nullable', Rule::in(['nuclear', 'joint'])],
                'family_status' => ['nullable', Rule::in(['middle_class', 'upper_middle', 'affluent', 'rich'])],
                'family_values' => ['nullable', Rule::in(['traditional', 'moderate', 'liberal'])],
                'father_occupation' => ['nullable', 'string', 'max:120'],
                'mother_occupation' => ['nullable', 'string', 'max:120'],
                'brothers' => ['nullable', 'integer', 'min:0', 'max:20'],
                'brothers_married' => ['nullable', 'integer', 'min:0', 'max:20', 'lte:brothers'],
                'sisters' => ['nullable', 'integer', 'min:0', 'max:20'],
                'sisters_married' => ['nullable', 'integer', 'min:0', 'max:20', 'lte:sisters'],
                'family_details' => ['nullable', 'string', 'max:2000'],
            ],
            'lifestyle' => [
                'diet' => ['nullable', Rule::in(['vegetarian', 'non_vegetarian', 'eggetarian', 'vegan'])],
                'smoking' => ['nullable', Rule::in(['no', 'occasionally', 'yes'])],
                'drinking' => ['nullable', Rule::in(['no', 'occasionally', 'yes'])],
                'lifestyle' => ['nullable', 'array'],
                'lifestyle.hobbies' => ['nullable', 'array'],
                'lifestyle.hobbies.*' => ['string', 'max:40'],
                'lifestyle.languages_known' => ['nullable', 'array'],
                'lifestyle.languages_known.*' => ['string', 'max:40'],
            ],
            'horoscope' => [
                'horoscope_enabled' => ['required', 'boolean'],
                'birth_time' => ['nullable', 'string', 'max:20'],
                'birth_place' => ['nullable', 'string', 'max:120'],
                'star' => ['nullable', 'string', 'max:60'],
                'rasi' => ['nullable', 'string', 'max:60'],
                'dosham' => ['nullable', Rule::in(['none', 'manglik', 'partial', 'dont_know'])],
            ],
            'about' => [
                'about_me' => ['nullable', 'string', 'max:3000'],
                'partner_expectations_note' => ['nullable', 'string', 'max:3000'],
            ],
            default => [],
        };
    }
}
