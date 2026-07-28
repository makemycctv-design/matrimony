<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use BelongsToCompany, HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'name',
        'email',
        'country_code',
        'mobile',
        'password',
        'status',
        'locale',
        'terms_accepted_at',
        'privacy_accepted_at',
        'marketing_opt_in',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'marketing_opt_in' => 'boolean',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'status' => UserStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->uuid ??= (string) Str::ulid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // --- Relationships ------------------------------------------------------

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(MemberProfile::class);
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }

    public function deletionRequests(): HasMany
    {
        return $this->hasMany(AccountDeletionRequest::class);
    }

    // --- Helpers ------------------------------------------------------------

    public function isMobileVerified(): bool
    {
        return $this->mobile_verified_at !== null;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_confirmed_at !== null;
    }

    /** Staff = anyone with a role other than the two member roles. */
    public function isStaff(): bool
    {
        return $this->hasAnyRole([
            'Super Admin', 'Platform Owner', 'Admin', 'Moderator',
            'Profile Verification Staff', 'Customer Support Staff',
            'Finance Staff', 'Marketing Staff',
        ]);
    }

    public function fullMobile(): ?string
    {
        return $this->mobile ? trim(($this->country_code ?? '').$this->mobile) : null;
    }

    /** Get the member's profile, creating a draft (with default preferences) if absent. */
    public function ensureProfile(): MemberProfile
    {
        // Query fresh (not the possibly-stale cached relation) so repeated calls
        // on the same instance never create a duplicate profile.
        $profile = $this->profile()->first();

        if ($profile === null) {
            $profile = new MemberProfile(['first_name' => explode(' ', $this->name)[0]]);
            $profile->company_id = $this->company_id;
            $this->profile()->save($profile);
            $profile->preferences()->create();
        }

        $this->setRelation('profile', $profile);

        return $profile;
    }
}
