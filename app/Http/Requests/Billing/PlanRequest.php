<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('plans.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'tier' => ['required', Rule::in(['free', 'basic', 'premium', 'vip', 'custom'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:1000000'], // rupees
            'gst_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'contact_view_access' => ['boolean'],
            'messaging_access' => ['boolean'],
            'advanced_search' => ['boolean'],
            'profile_boost' => ['boolean'],
            'profile_highlight' => ['boolean'],
            'verification_priority' => ['boolean'],
            'max_interests_per_day' => ['nullable', 'integer', 'min:0'],
            'max_profile_views_per_day' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:120'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Map validated input to model attributes (rupees -> paise, derive slug).
     *
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        $data = $this->validated();
        $data['price_paise'] = (int) round(((float) $data['price']) * 100);
        unset($data['price']);
        $data['slug'] = Str::slug($data['name']);

        return $data;
    }
}
