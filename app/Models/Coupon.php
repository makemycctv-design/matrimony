<?php

namespace App\Models;

use App\Enums\CouponType;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Coupon extends Model
{
    use BelongsToCompany, SoftDeletes;

    protected $guarded = ['id', 'uuid', 'company_id'];

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'integer',
            'max_discount_paise' => 'integer',
            'min_amount_paise' => 'integer',
            'applicable_plan_ids' => 'array',
            'is_referral' => 'boolean',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (Coupon $c) => $c->uuid ??= (string) Str::ulid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function isWithinWindow(): bool
    {
        $now = Carbon::now();

        return (! $this->starts_at || $this->starts_at->lte($now))
            && (! $this->expires_at || $this->expires_at->gte($now));
    }

    public function hasRedemptionsLeft(): bool
    {
        return $this->max_redemptions === null || $this->redeemed_count < $this->max_redemptions;
    }
}
