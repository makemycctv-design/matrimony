<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Support\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adds optional tenant (company) ownership to a model.
 *
 * When a current tenant is resolved (see TenantManager) a global scope
 * automatically constrains queries to that company and new records are
 * stamped with the company_id. For a single installation with no active
 * tenant, behaviour is unchanged.
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::creating(function ($model): void {
            if (empty($model->company_id)) {
                $tenant = app(TenantManager::class)->current();

                if ($tenant !== null) {
                    $model->company_id = $tenant->id;
                }
            }
        });

        static::addGlobalScope('company', function (Builder $builder): void {
            $tenant = app(TenantManager::class)->current();

            if ($tenant !== null) {
                $builder->where($builder->getModel()->getTable().'.company_id', $tenant->id);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Query without the tenant global scope (admin/cross-tenant tooling). */
    public function scopeWithoutCompanyScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('company');
    }
}
