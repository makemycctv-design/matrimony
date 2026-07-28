<?php

namespace Tests\Feature\Billing;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function staff(string $role = 'Admin'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_admin_can_create_a_plan(): void
    {
        $this->actingAs($this->staff())
            ->post(route('admin.plans.store'), [
                'name' => 'Gold', 'tier' => 'premium', 'price' => 2999, 'gst_percent' => 18,
                'duration_days' => 90, 'contact_view_access' => true, 'per_user_limit' => 1,
            ])
            ->assertSessionHasNoErrors();

        $plan = SubscriptionPlan::where('name', 'Gold')->firstOrFail();
        $this->assertSame(299900, $plan->price_paise); // rupees -> paise
        $this->assertTrue($plan->contact_view_access);
    }

    public function test_members_cannot_manage_plans(): void
    {
        $member = User::factory()->create();
        $member->assignRole('Registered Member');

        $this->actingAs($member)
            ->post(route('admin.plans.store'), ['name' => 'X', 'tier' => 'basic', 'price' => 10, 'gst_percent' => 18, 'duration_days' => 30])
            ->assertForbidden();
    }

    public function test_admin_can_create_a_coupon(): void
    {
        $this->actingAs($this->staff())
            ->post(route('admin.coupons.store'), [
                'code' => 'FEST25', 'type' => 'percent', 'value' => 25, 'per_user_limit' => 2,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('coupons', ['code' => 'FEST25', 'value' => 25]);
    }

    public function test_revenue_dashboard_requires_reports_permission(): void
    {
        $this->actingAs($this->staff('Admin'))->get(route('admin.revenue.index'))->assertOk();

        $support = User::factory()->create();
        $support->assignRole('Customer Support Staff'); // no reports.view
        $this->actingAs($support)->get(route('admin.revenue.index'))->assertForbidden();
    }
}
