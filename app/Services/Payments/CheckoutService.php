<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Prepares a server-side order for Razorpay Checkout. The client only ever
 * receives amounts and a provider order id computed here — never the plan
 * price or discount logic — so totals cannot be tampered with.
 */
class CheckoutService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly BillingCalculator $calculator,
        private readonly CouponService $coupons,
    ) {}

    /**
     * @return array{payment:Payment, order_id:?string, key:?string, breakdown:array<string,mixed>}
     */
    public function createOrder(User $user, SubscriptionPlan $plan, ?string $couponCode = null): array
    {
        if (! $plan->is_active) {
            throw ValidationException::withMessages(['plan' => 'This plan is not available.']);
        }

        [$coupon, $discount] = $this->coupons->apply($couponCode, $user, $plan);
        $breakdown = $this->calculator->compute($plan, $coupon);

        $payment = new Payment([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'coupon_id' => $coupon?->id,
            'subtotal_paise' => $breakdown['subtotal_paise'],
            'discount_paise' => $breakdown['discount_paise'],
            'tax_paise' => $breakdown['tax_paise'],
            'amount_paise' => $breakdown['total_paise'],
            'currency' => $plan->currency,
            'status' => PaymentStatus::Created,
            'idempotency_key' => (string) Str::uuid(),
            'meta' => ['gst_percent' => $breakdown['gst_percent'], 'coupon_code' => $coupon?->code],
        ]);
        $payment->company_id = $user->company_id;

        // Zero-value orders (free plan or 100% coupon) skip the gateway entirely.
        if ($breakdown['total_paise'] > 0) {
            $order = $this->gateway->createOrder(
                $breakdown['total_paise'],
                'rcpt_'.Str::random(12),
                ['user_uuid' => $user->uuid, 'plan' => $plan->slug],
            );
            $payment->razorpay_order_id = $order['id'];
        }

        $payment->save();

        return [
            'payment' => $payment,
            'order_id' => $payment->razorpay_order_id,
            'key' => $this->gateway->publicKey(),
            'breakdown' => $breakdown,
        ];
    }
}
