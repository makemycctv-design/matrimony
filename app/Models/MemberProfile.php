<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\ProfileStatus;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\MemberProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MemberProfile extends Model
{
    /** @use HasFactory<MemberProfileFactory> */
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $guarded = ['id', 'uuid', 'company_id', 'profile_code', 'is_verified', 'verified_at'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'lifestyle' => 'array',
            'horoscope_enabled' => 'boolean',
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
            'is_photo_verified' => 'boolean',
            'verified_at' => 'datetime',
            'last_active_at' => 'datetime',
            'annual_income_min' => 'integer',
            'annual_income_max' => 'integer',
            'height_cm' => 'integer',
            'completion_percentage' => 'integer',
            'status' => ProfileStatus::class,
            'gender' => Gender::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MemberProfile $profile): void {
            $profile->uuid ??= (string) Str::ulid();
        });

        static::created(function (MemberProfile $profile): void {
            if (empty($profile->profile_code)) {
                $profile->forceFill([
                    'profile_code' => 'MTR'.str_pad((string) $profile->id, 6, '0', STR_PAD_LEFT),
                ])->saveQuietly();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // --- Relationships ------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function preferences(): HasOne
    {
        return $this->hasOne(ProfilePreference::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ProfilePhoto::class)->orderBy('sort_order');
    }

    public function primaryPhoto(): HasOne
    {
        return $this->hasOne(ProfilePhoto::class)->where('is_primary', true);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProfileDocument::class)->latest('id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(ProfileVerification::class)->latest('id');
    }

    public function partnerPreference(): HasOne
    {
        return $this->hasOne(PartnerPreference::class);
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function sentInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'sender_profile_id');
    }

    public function receivedInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'receiver_profile_id');
    }

    public function shortlists(): HasMany
    {
        return $this->hasMany(Shortlist::class, 'member_profile_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(BlockedProfile::class, 'member_profile_id');
    }

    /** IDs this profile has blocked, and IDs that have blocked this profile. */
    public function blockedIds(): array
    {
        return BlockedProfile::query()
            ->where('member_profile_id', $this->id)
            ->pluck('blocked_profile_id')
            ->all();
    }

    public function blockedByIds(): array
    {
        return BlockedProfile::query()
            ->where('blocked_profile_id', $this->id)
            ->pluck('member_profile_id')
            ->all();
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    public function caste(): BelongsTo
    {
        return $this->belongsTo(Caste::class);
    }

    public function subCaste(): BelongsTo
    {
        return $this->belongsTo(SubCaste::class);
    }

    public function motherTongue(): BelongsTo
    {
        return $this->belongsTo(MotherTongue::class);
    }

    public function education(): BelongsTo
    {
        return $this->belongsTo(Education::class);
    }

    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'country_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'state_id');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'district_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'city_id');
    }

    // --- Computed attributes ------------------------------------------------

    protected function age(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->date_of_birth
            ? (int) $this->date_of_birth->diffInYears(Carbon::now())
            : null);
    }

    /** Name rendered according to the member's name_display privacy setting. */
    protected function displayName(): Attribute
    {
        return Attribute::get(function (): string {
            $first = $this->first_name ?? '';
            $last = $this->last_name ?? '';

            return match ($this->name_display) {
                'full' => trim("$first $last"),
                'initials' => trim(Str::substr($first, 0, 1).'. '.Str::substr($last, 0, 1).'.'),
                'hidden' => $this->profile_code ?? 'Member',
                default => $first, // first_only
            };
        });
    }

    // --- Scopes -------------------------------------------------------------

    /** Only profiles eligible to appear in search / matching. */
    public function scopeDiscoverable(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [
                ProfileStatus::Verified->value,
                ProfileStatus::Submitted->value,
                ProfileStatus::UnderReview->value,
            ])
            ->whereHas('preferences', fn (Builder $q) => $q->where('appear_in_search', true))
            ->orWhereDoesntHave('preferences');
    }

    public function scopeForGender(Builder $query, Gender|string $gender): Builder
    {
        return $query->where('gender', $gender instanceof Gender ? $gender->value : $gender);
    }

    public function isDiscoverable(): bool
    {
        return $this->status instanceof ProfileStatus && $this->status->isDiscoverable();
    }
}
