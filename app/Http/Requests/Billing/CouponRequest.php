<?php

namespace App\Http\Requests\Billing;

use App\Enums\CouponType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('coupons.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:40', 'alpha_num'],
            'type' => ['required', new Enum(CouponType::class)],
            // percent (0-100) or rupees for fixed — normalised in toAttributes().
            'value' => ['required', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'], // rupees cap for percent
            'min_amount' => ['nullable', 'numeric', 'min:0'],   // rupees
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['required', 'integer', 'min:1', 'max:100'],
            'is_referral' => ['boolean'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:starts_at'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        $v = $this->validated();

        return [
            'code' => strtoupper($v['code']),
            'type' => $v['type'],
            'value' => $v['type'] === 'percent' ? (int) $v['value'] : (int) round(((float) $v['value']) * 100),
            'max_discount_paise' => isset($v['max_discount']) ? (int) round(((float) $v['max_discount']) * 100) : null,
            'min_amount_paise' => (int) round(((float) ($v['min_amount'] ?? 0)) * 100),
            'max_redemptions' => $v['max_redemptions'] ?? null,
            'per_user_limit' => $v['per_user_limit'],
            'is_referral' => (bool) ($v['is_referral'] ?? false),
            'is_active' => (bool) ($v['is_active'] ?? true),
            'starts_at' => $v['starts_at'] ?? null,
            'expires_at' => $v['expires_at'] ?? null,
        ];
    }
}
