<?php

namespace App\Http\Requests\Search;

use App\Services\Matching\MatchingSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartnerPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preferred_gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'age_min' => ['nullable', 'integer', 'min:18', 'max:100'],
            'age_max' => ['nullable', 'integer', 'min:18', 'max:100', 'gte:age_min'],
            'height_min_cm' => ['nullable', 'integer', 'min:120', 'max:230'],
            'height_max_cm' => ['nullable', 'integer', 'min:120', 'max:230', 'gte:height_min_cm'],
            'marital_statuses' => ['nullable', 'array'],
            'religion_ids' => ['nullable', 'array'],
            'caste_ids' => ['nullable', 'array'],
            'mother_tongue_ids' => ['nullable', 'array'],
            'education_ids' => ['nullable', 'array'],
            'profession_ids' => ['nullable', 'array'],
            'country_ids' => ['nullable', 'array'],
            'state_ids' => ['nullable', 'array'],
            'district_ids' => ['nullable', 'array'],
            'city_ids' => ['nullable', 'array'],
            'diets' => ['nullable', 'array'],
            'employment_types' => ['nullable', 'array'],
            'income_min' => ['nullable', 'integer', 'min:0'],
            'income_max' => ['nullable', 'integer', 'min:0', 'gte:income_min'],
            'accept_physically_challenged' => ['nullable', 'boolean'],
            'horoscope_match_required' => ['nullable', 'boolean'],
            'only_verified' => ['nullable', 'boolean'],
            'only_with_photo' => ['nullable', 'boolean'],
            'weight_overrides' => ['nullable', 'array'],
            'weight_overrides.*' => ['integer', 'min:0', 'max:100'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $allowed = array_keys(MatchingSettingsService::DEFAULT_WEIGHTS);
            foreach (array_keys((array) $this->input('weight_overrides', [])) as $factor) {
                if (! in_array($factor, $allowed, true)) {
                    $validator->errors()->add('weight_overrides', "Unknown weighting factor: {$factor}");
                }
            }
        });
    }
}
