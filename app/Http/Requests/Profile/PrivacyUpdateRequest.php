<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrivacyUpdateRequest extends FormRequest
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
        $audience = Rule::in(['everyone', 'members', 'verified', 'premium', 'connected', 'none']);

        return [
            'photo_visibility' => ['required', $audience],
            'contact_visibility' => ['required', $audience],
            'horoscope_visibility' => ['required', $audience],
            'income_visibility' => ['required', $audience],
            'interest_from' => ['required', Rule::in(['everyone', 'members', 'verified', 'premium'])],

            'appear_in_search' => ['required', 'boolean'],
            'visible_to_verified_only' => ['required', 'boolean'],
            'visible_to_premium_only' => ['required', 'boolean'],
            'hide_details_until_interest_accepted' => ['required', 'boolean'],
            'show_online_status' => ['required', 'boolean'],
            'show_last_seen' => ['required', 'boolean'],

            'notify_email' => ['required', 'boolean'],
            'notify_sms' => ['required', 'boolean'],
            'notify_whatsapp' => ['required', 'boolean'],
            'notify_in_app' => ['required', 'boolean'],
            'notify_push' => ['required', 'boolean'],
        ];
    }
}
