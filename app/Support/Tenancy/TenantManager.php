<?php

namespace App\Support\Tenancy;

use App\Models\Company;

/**
 * Resolves and holds the active tenant (company) for the request lifecycle.
 *
 * For a single matrimony installation this typically stays null (no scoping)
 * or is bound to the one company. For SaaS usage a middleware would resolve
 * the tenant from the domain/subdomain and call setCurrent().
 */
class TenantManager
{
    protected ?Company $current = null;

    public function setCurrent(?Company $company): void
    {
        $this->current = $company;
    }

    public function current(): ?Company
    {
        return $this->current;
    }

    public function hasTenant(): bool
    {
        return $this->current !== null;
    }

    public function id(): ?int
    {
        return $this->current?->id;
    }

    public function forget(): void
    {
        $this->current = null;
    }
}
