<?php

namespace App\Models\Concerns;

use App\Support\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared behaviour for configurable lookup tables (religion, education, etc.):
 * translatable names, active filtering, ordering, and optional tenant scoping.
 */
trait IsMasterData
{
    public function initializeIsMasterData(): void
    {
        $this->mergeCasts([
            'name_translations' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);
    }

    public static function bootIsMasterData(): void
    {
        static::creating(function ($model): void {
            if (empty($model->company_id)) {
                $model->company_id = app(TenantManager::class)->id();
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /** Localized name with graceful fallback to the base name. */
    public function localizedName(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $this->name_translations[$locale] ?? $this->name;
    }
}
