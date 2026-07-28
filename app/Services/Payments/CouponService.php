<?php

namespace App\Services\Payments;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Validates and applies discount/referral coupons. All monetary values are in
 * paise. Validation failures raise a ValidationException keyed "coupon" so the
 * UI can surface a friendly message.
 */
class CouponService
{
    public function __construct(private readonly BillingCalculator $calculator) {}

    /** Resolve an active coupon by code (case-insensitive) or fail. */
    public function findByCode(string $code): Coupon
    {
        $coupon = Coupon::query()->where('code', strtoupper(trim($code)))->first();

        if ($coupon === null) {
            throw ValidationException::withMessages(['coupon' => 'That coupon code is not valid.']);
        }

        return $coupon;
    }

    /**
     * Assert the coupon may be used by this user for this plan/amount.
     */
    public function assertUsable(Coupon $coupon, User $user, SubscriptionPlan $plan, int $subtotalPaise): void
    {
        $fail = fn (string $msg) => throw ValidationException::withMessages(['coupon' => $msg]);

        if (! $coupon->is_active) {
            $fail('This coupon is no longer active.');
        }
        if (! $coupon->isWithinWindow()) {
            $fail('This coupon has expired or is not yet active.');
        }
        if (! $coupon->hasRedemptionsLeft()) {
            $fail('This coupon has reached its redemption limit.');
        }
        if ($subtotalPaise < (int) $coupon->min_amount_paise) {
            $fail('This coupon requires a higher order value.');
        }
        if (! empty($coupon->applicable_plan_ids) && ! in_array($plan->id, $coupon->applicable_plan_ids, true)) {
            $fail('This coupon does not apply to the selected plan.');
        }

        $used = CouponRedemption::where('coupon_id', $coupon->id)->where('user_id', $user->id)->count();
        if ($used >= (int) $coupon->per_user_limit) {
            $fail('You have already used this coupon.');
        }
    }

    /** Validate + return [coupon, discountPaise] for a plan, or [null, 0]. */
    public function apply(?string $code, User $user, SubscriptionPlan $plan): array
    {
        if (blank($code)) {
            return [null, 0];
        }

        $coupon = $this->findByCode($code);
        $this->assertUsable($coupon, $user, $plan, (int) $plan->price_paise);

        return [$coupon, $this->calculator->discountFor($coupon, (int) $plan->price_paise)];
    }

    /** Record a redemption and increment the usage counter. Call post-payment. */
    public function redeem(Coupon $coupon, User $user, Payment $payment, int $discountPaise): void
    {
        $redemption = new CouponRedemption([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'discount_paise' => $discountPaise,
        ]);
        $redemption->company_id = $user->company_id;
        $redemption->save();

        $coupon->increment('redeemed_count');
    }
}
