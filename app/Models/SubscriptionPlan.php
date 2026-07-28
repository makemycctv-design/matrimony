<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\SubscriptionPlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SubscriptionPlan extends Model
{
    /** @use HasFactory<SubscriptionPlanFactory> */
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $guarded = ['id', 'uuid', 'company_id'];

    protected function casts(): array
    {
        return [
            'price_paise' => 'integer',
            'gst_percent' => 'decimal:2',
            'duration_days' => 'integer',
            'trial_days' => 'integer',
            'contact_view_access' => 'boolean',
            'messaging_access' => 'boolean',
            'advanced_search' => 'boolean',
            'profile_boost' => 'boolean',
            'profile_highlight' => 'boolean',
            'verification_priority' => 'boolean',
            'features' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (SubscriptionPlan $p) => $p->uuid ??= (string) Str::ulid());
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isFree(): bool
    {
        return $this->price_paise === 0;
    }

    /** Snapshot of entitlements stored on a subscription at purchase time. */
    public function entitlements(): array
    {
        return [
            'contact_view_access' => $this->contact_view_access,
            'messaging_access' => $this->messaging_access,
            'advanced_search' => $this->advanced_search,
            'profile_boost' => $this->profile_boost,
            'profile_highlight' => $this->profile_highlight,
            'verification_priority' => $this->verification_priority,
            'max_profile_views_per_day' => $this->max_profile_views_per_day,
            'max_interests_per_day' => $this->max_interests_per_day,
            'max_contact_views' => $this->max_contact_views,
        ];
    }
}
