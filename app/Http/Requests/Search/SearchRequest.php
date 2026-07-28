<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchRequest extends FormRequest
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
            'keyword' => ['nullable', 'string', 'max:100'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'age_min' => ['nullable', 'integer', 'min:18', 'max:100'],
            'age_max' => ['nullable', 'integer', 'min:18', 'max:100', 'gte:age_min'],
            'height_min' => ['nullable', 'integer', 'min:120', 'max:230'],
            'height_max' => ['nullable', 'integer', 'min:120', 'max:230', 'gte:height_min'],
            'marital_statuses' => ['nullable', 'array'],
            'marital_statuses.*' => ['string'],
            'religion_ids' => ['nullable', 'array'],
            'religion_ids.*' => ['integer'],
            'caste_ids' => ['nullable', 'array'],
            'caste_ids.*' => ['integer'],
            'mother_tongue_ids' => ['nullable', 'array'],
            'mother_tongue_ids.*' => ['integer'],
            'education_ids' => ['nullable', 'array'],
            'education_ids.*' => ['integer'],
            'profession_ids' => ['nullable', 'array'],
            'profession_ids.*' => ['integer'],
            'country_id' => ['nullable', 'integer'],
            'state_id' => ['nullable', 'integer'],
            'district_id' => ['nullable', 'integer'],
            'city_id' => ['nullable', 'integer'],
            'income_min' => ['nullable', 'integer', 'min:0'],
            'income_max' => ['nullable', 'integer', 'min:0'],
            'diets' => ['nullable', 'array'],
            'only_verified' => ['nullable', 'boolean'],
            'only_with_photo' => ['nullable', 'boolean'],
            'only_premium' => ['nullable', 'boolean'],
            'recently_active' => ['nullable', 'boolean'],
            'online' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(['relevance', 'newest', 'active', 'age_asc', 'age_desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return $this->safe()->except(['sort', 'page']);
    }
}
