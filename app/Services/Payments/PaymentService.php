<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Notifications\Payment\PaymentFailedNotification;
use App\Notifications\Payment\PaymentReceiptNotification;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Orchestrates the authoritative payment lifecycle: signature verification,
 * capture, subscription activation, invoice generation and coupon redemption —
 * all inside one transaction and fully idempotent (a payment is only ever
 * captured once, whether triggered by the browser callback or the webhook).
 */
class PaymentService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly SubscriptionService $subscriptions,
        private readonly InvoiceService $invoices,
        private readonly CouponService $coupons,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Verify a Razorpay Checkout callback and, if valid, capture the payment.
     */
    public function verifyAndCapture(Payment $payment, string $orderId, string $paymentId, string $signature): Payment
    {
        $valid = $this->gateway->verifyPaymentSignature($orderId, $paymentId, $signature);

        $payment->forceFill([
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $signature,
            'signature_verified' => $valid,
        ])->save();

        if (! $valid) {
            $this->markFailed($payment, 'Signature verification failed');

            throw ValidationException::withMessages(['payment' => 'We could not verify this payment. If money was deducted it will be auto-refunded.']);
        }

        return $this->capture($payment, $paymentId);
    }

    /**
     * Capture a payment and provision everything it pays for. Idempotent.
     */
    public function capture(Payment $payment, ?string $paymentId = null, ?string $method = null): Payment
    {
        if ($payment->status === PaymentStatus::Captured) {
            return $payment; // Already provisioned — do nothing (idempotency).
        }

        DB::transaction(function () use ($payment, $paymentId, $method): void {
            $payment->forceFill([
                'status' => PaymentStatus::Captured,
                'razorpay_payment_id' => $paymentId ?? $payment->razorpay_payment_id,
                'method' => $method ?? $payment->method,
                'paid_at' => Carbon::now(),
            ])->save();

            $this->subscriptions->activateFromPayment($payment);
            $this->invoices->generateFor($payment);

            if ($payment->coupon) {
                $this->coupons->redeem($payment->coupon, $payment->user, $payment, $payment->discount_paise);
            }

            $this->audit->log('payment.captured', $payment, "Payment {$payment->uuid} captured");
        });

        $payment->user?->notify(new PaymentReceiptNotification($payment->fresh()));

        return $payment->fresh();
    }

    public function markFailed(Payment $payment, ?string $reason = null): Payment
    {
        if ($payment->status->isPaid()) {
            return $payment; // Never downgrade a captured payment.
        }

        $payment->forceFill([
            'status' => PaymentStatus::Failed,
            'failure_reason' => $reason,
        ])->save();

        $payment->user?->notify(new PaymentFailedNotification($payment));

        return $payment;
    }
}
