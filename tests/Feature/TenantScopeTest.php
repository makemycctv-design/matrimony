<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Support\Tenancy\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_queries_are_scoped_to_the_current_tenant(): void
    {
        $acme = Company::factory()->create();
        $globex = Company::factory()->create();

        User::factory()->create(['company_id' => $acme->id]);
        User::factory()->create(['company_id' => $acme->id]);
        User::factory()->create(['company_id' => $globex->id]);

        $manager = app(TenantManager::class);

        // No tenant → everything is visible.
        $manager->forget();
        $this->assertSame(3, User::count());

        // Scoped to Acme → only Acme users.
        $manager->setCurrent($acme);
        $this->assertSame(2, User::count());

        // New records inherit the active tenant automatically.
        User::factory()->create();
        $this->assertSame(3, User::count());
        $this->assertSame(4, User::withoutGlobalScope('company')->count());

        $manager->forget();
    }

    public function test_new_records_are_stamped_with_the_active_company(): void
    {
        $company = Company::factory()->create();
        app(TenantManager::class)->setCurrent($company);

        $user = User::factory()->create();

        $this->assertSame($company->id, $user->company_id);

        app(TenantManager::class)->forget();
    }
}
