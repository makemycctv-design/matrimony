<?php

namespace App\Services\Payments;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\SubscriptionPlan;

/**
 * Computes an order's money breakdown, all in integer paise (never floats).
 *
 * Model: plan price is the pre-tax base. Discount applies to the base, GST is
 * charged on the discounted base, and total = base - discount + tax. GST is
 * split CGST+SGST (intra-state) unless an inter-state flag is set (then IGST).
 */
class BillingCalculator
{
    /**
     * @return array{
     *   subtotal_paise:int, discount_paise:int, taxable_paise:int, tax_paise:int,
     *   cgst_paise:int, sgst_paise:int, igst_paise:int, total_paise:int, gst_percent:float
     * }
     */
    public function compute(SubscriptionPlan $plan, ?Coupon $coupon = null, bool $interState = false): array
    {
        $subtotal = (int) $plan->price_paise;
        $discount = $coupon ? $this->discountFor($coupon, $subtotal) : 0;
        $discount = min($discount, $subtotal);

        $taxable = $subtotal - $discount;
        $gstPercent = (float) $plan->gst_percent;
        $tax = (int) round($taxable * $gstPercent / 100);

        [$cgst, $sgst, $igst] = $interState
            ? [0, 0, $tax]
            : [intdiv($tax, 2), $tax - intdiv($tax, 2), 0];

        return [
            'subtotal_paise' => $subtotal,
            'discount_paise' => $discount,
            'taxable_paise' => $taxable,
            'tax_paise' => $tax,
            'cgst_paise' => $cgst,
            'sgst_paise' => $sgst,
            'igst_paise' => $igst,
            'total_paise' => $taxable + $tax,
            'gst_percent' => $gstPercent,
        ];
    }

    /** Raw discount for a coupon against a base amount (paise), capped. */
    public function discountFor(Coupon $coupon, int $subtotalPaise): int
    {
        $discount = $coupon->type === CouponType::Percent
            ? (int) round($subtotalPaise * $coupon->value / 100)
            : (int) $coupon->value;

        if ($coupon->max_discount_paise !== null) {
            $discount = min($discount, (int) $coupon->max_discount_paise);
        }

        return max(0, min($discount, $subtotalPaise));
    }
}
