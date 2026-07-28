<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'lowercase', $this->emailRule(), 'max:255', Rule::unique(User::class, 'email')],
            'country_code' => ['nullable', 'string', 'max:5'],
            'mobile' => ['required', 'string', 'regex:/^[0-9]{7,15}$/', Rule::unique(User::class, 'mobile')],
            'gender' => ['required', 'in:male,female,other'],
            'profile_for' => ['nullable', 'in:self,son,daughter,brother,sister,relative,friend'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'accept_terms' => ['accepted'],
            'accept_privacy' => ['accepted'],
            'marketing_opt_in' => ['nullable', 'boolean'],
            'locale' => ['nullable', 'in:en,ml'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'Please enter a valid mobile number (digits only).',
            'accept_terms.accepted' => 'You must accept the Terms of Service to register.',
            'accept_privacy.accepted' => 'You must accept the Privacy Policy to register.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mobile' => preg_replace('/[^0-9]/', '', (string) $this->input('mobile')),
        ]);
    }

    /** Enforce a deliverable email (DNS) only in production to keep dev/tests fast. */
    protected function emailRule(): string
    {
        return app()->isProduction() ? 'email:rfc,dns' : 'email:rfc';
    }
}
