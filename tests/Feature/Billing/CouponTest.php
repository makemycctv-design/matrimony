<?php

namespace Tests\Feature\Billing;

use App\Models\Coupon;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    private function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::factory()->create(['price_paise' => 200000, 'gst_percent' => 18]);
    }

    public function test_percentage_coupon_reduces_the_subtotal(): void
    {
        $user = User::factory()->create();
        $plan = $this->plan();
        Coupon::create(['code' => 'SAVE10', 'type' => 'percent', 'value' => 10, 'per_user_limit' => 1, 'is_active' => true]);

        $result = app(CheckoutService::class)->createOrder($user, $plan, 'save10');

        $this->assertSame(20000, $result['payment']->discount_paise); // 10% of 200000
        $this->assertSame(200000 - 20000, $result['payment']->subtotal_paise - $result['payment']->discount_paise);
    }

    public function test_expired_coupon_is_rejected(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::create(['code' => 'OLD', 'type' => 'percent', 'value' => 10, 'per_user_limit' => 1, 'is_active' => true, 'expires_at' => Carbon::now()->subDay()]);

        $this->expectException(ValidationException::class);
        app(CouponService::class)->assertUsable($coupon, $user, $this->plan(), 200000);
    }

    public function test_per_user_limit_is_enforced(): void
    {
        $user = User::factory()->create();
        $plan = $this->plan();
        $coupon = Coupon::create(['code' => 'ONCE', 'type' => 'fixed', 'value' => 10000, 'per_user_limit' => 1, 'is_active' => true]);

        // Simulate a prior redemption.
        $coupon->redemptions()->create(['user_id' => $user->id, 'discount_paise' => 10000]);

        $this->expectException(ValidationException::class);
        app(CouponService::class)->assertUsable($coupon, $user, $plan, 200000);
    }
}
