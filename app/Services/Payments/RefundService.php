<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Notifications\Payment\RefundProcessedNotification;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Refund request → approval → processing workflow. Members raise a request;
 * finance staff approve and process it, which calls the gateway and updates the
 * payment's refunded total and status.
 */
class RefundService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
        private readonly AuditLogger $audit,
    ) {}

    public function request(Payment $payment, User $requester, ?int $amountPaise = null, ?string $reason = null): Refund
    {
        if (! $payment->status->isRefundable()) {
            throw ValidationException::withMessages(['refund' => 'This payment is not eligible for a refund.']);
        }

        $amount = $amountPaise ?? $payment->refundablePaise();

        if ($amount <= 0 || $amount > $payment->refundablePaise()) {
            throw ValidationException::withMessages(['refund' => 'The refund amount exceeds the refundable balance.']);
        }

        if ($payment->refunds()->whereIn('status', [RefundStatus::Requested->value, RefundStatus::Approved->value])->exists()) {
            throw ValidationException::withMessages(['refund' => 'A refund request for this payment is already in progress.']);
        }

        $refund = new Refund([
            'payment_id' => $payment->id,
            'requested_by' => $requester->id,
            'amount_paise' => $amount,
            'reason' => $reason,
            'status' => RefundStatus::Requested,
        ]);
        $refund->company_id = $payment->company_id;
        $refund->save();

        return $refund;
    }

    public function approve(Refund $refund, User $staff): Refund
    {
        $refund->forceFill(['status' => RefundStatus::Approved, 'processed_by' => $staff->id])->save();

        return $refund;
    }

    public function reject(Refund $refund, User $staff, ?string $notes = null): Refund
    {
        $refund->forceFill([
            'status' => RefundStatus::Rejected,
            'processed_by' => $staff->id,
            'notes' => $notes,
            'processed_at' => Carbon::now(),
        ])->save();

        return $refund;
    }

    /** Execute the refund at the gateway and reconcile the payment. */
    public function process(Refund $refund, User $staff): Refund
    {
        $payment = $refund->payment;

        DB::transaction(function () use ($refund, $payment, $staff): void {
            $result = ['id' => null, 'status' => 'processed'];

            if ($payment->razorpay_payment_id && $this->gateway->isLive()) {
                $result = $this->gateway->refund($payment->razorpay_payment_id, $refund->amount_paise);
            }

            $refund->forceFill([
                'status' => RefundStatus::Processed,
                'razorpay_refund_id' => $result['id'] ?? null,
                'processed_by' => $staff->id,
                'processed_at' => Carbon::now(),
            ])->save();

            $refunded = min($payment->amount_paise, $payment->refunded_paise + $refund->amount_paise);
            $payment->forceFill([
                'refunded_paise' => $refunded,
                'status' => $refunded >= $payment->amount_paise
                    ? PaymentStatus::Refunded
                    : PaymentStatus::PartiallyRefunded,
            ])->save();

            $this->audit->log('refund.processed', $refund, "Refund {$refund->uuid} processed", null, ['amount_paise' => $refund->amount_paise]);
        });

        $payment->user?->notify(new RefundProcessedNotification($refund));

        return $refund->fresh();
    }
}
