<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Processes provider webhooks as the authoritative source of payment truth.
 * Replay/idempotency protection is enforced via the unique provider event id:
 * an event that was already recorded is never processed a second time.
 */
class WebhookService
{
    public function __construct(private readonly PaymentService $payments) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $eventId, string $eventType, array $payload, bool $signatureValid): PaymentWebhookEvent
    {
        // First writer wins; a duplicate delivery finds the existing row.
        $event = PaymentWebhookEvent::firstOrCreate(
            ['event_id' => $eventId],
            ['event_type' => $eventType, 'payload' => $payload, 'signature_verified' => $signatureValid, 'status' => 'received'],
        );

        // Already handled (idempotency) — do nothing further.
        if (! $event->wasRecentlyCreated) {
            return $event;
        }

        if (! $signatureValid) {
            $event->forceFill(['status' => 'failed', 'notes' => 'Invalid signature', 'processed_at' => Carbon::now()])->save();

            return $event;
        }

        $status = $this->route($eventType, $payload);

        $event->forceFill(['status' => $status, 'processed_at' => Carbon::now()])->save();

        return $event;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function route(string $eventType, array $payload): string
    {
        $entity = Arr::get($payload, 'payload.payment.entity', []);
        $orderId = $entity['order_id'] ?? null;
        $paymentId = $entity['id'] ?? null;
        $method = $entity['method'] ?? null;

        $payment = $this->locatePayment($orderId, $paymentId);

        if ($payment === null) {
            return 'ignored';
        }

        return match ($eventType) {
            'payment.captured', 'order.paid' => tap('processed', fn () => $this->payments->capture($payment, $paymentId, $method)),
            'payment.failed' => tap('processed', fn () => $this->payments->markFailed($payment, $entity['error_description'] ?? 'Payment failed at gateway')),
            'refund.processed', 'refund.created' => tap('processed', fn () => $this->recordRefund($payment, Arr::get($payload, 'payload.refund.entity', []))),
            default => 'ignored',
        };
    }

    private function locatePayment(?string $orderId, ?string $paymentId): ?Payment
    {
        return Payment::query()
            ->when($orderId, fn ($q) => $q->orWhere('razorpay_order_id', $orderId))
            ->when($paymentId, fn ($q) => $q->orWhere('razorpay_payment_id', $paymentId))
            ->first();
    }

    /**
     * @param  array<string, mixed>  $refundEntity
     */
    private function recordRefund(Payment $payment, array $refundEntity): void
    {
        $amount = (int) ($refundEntity['amount'] ?? 0);
        $refunded = min($payment->amount_paise, $payment->refunded_paise + $amount);

        $payment->forceFill([
            'refunded_paise' => $refunded,
            'status' => $refunded >= $payment->amount_paise
                ? PaymentStatus::Refunded->value
                : PaymentStatus::PartiallyRefunded->value,
        ])->save();
    }
}
