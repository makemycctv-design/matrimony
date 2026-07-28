<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'legal_name', 'email', 'phone', 'website',
        'logo_path', 'favicon_path', 'primary_color', 'secondary_color',
        'default_locale', 'supported_locales', 'timezone', 'currency',
        'gstin', 'pan', 'address', 'is_active', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'supported_locales' => 'array',
            'address' => 'array',
            'settings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Populate the public ULID on create. We use a ULID string in the `uuid`
     * column rather than the model route key so integer ids stay internal.
     */
    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            $company->uuid ??= (string) Str::ulid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
