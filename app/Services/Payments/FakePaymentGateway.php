<?php

namespace App\Services\Payments;

use Illuminate\Support\Str;

/**
 * Safe offline gateway for local development, CI and tests. It never contacts
 * an external service. Signatures are computed with the configured secret so
 * the full verify/activate flow can be exercised deterministically in tests.
 */
class FakePaymentGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $secret = 'fake_secret',
        private readonly string $webhookSecret = 'fake_webhook_secret',
    ) {}

    public function createOrder(int $amountPaise, string $receipt, array $notes = []): array
    {
        return [
            'id' => 'order_'.Str::random(14),
            'amount' => $amountPaise,
            'currency' => 'INR',
        ];
    }

    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        return hash_equals($this->signPayment($orderId, $paymentId), $signature);
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        return hash_equals(hash_hmac('sha256', $payload, $this->webhookSecret), $signature);
    }

    public function fetchPayment(string $paymentId): array
    {
        return ['id' => $paymentId, 'status' => 'captured', 'method' => 'upi'];
    }

    public function refund(string $paymentId, int $amountPaise): array
    {
        return ['id' => 'rfnd_'.Str::random(14), 'status' => 'processed'];
    }

    public function publicKey(): ?string
    {
        return 'rzp_test_fake';
    }

    public function isLive(): bool
    {
        return false;
    }

    /** Test helper: produce a valid Checkout signature for the given ids. */
    public function signPayment(string $orderId, string $paymentId): string
    {
        return hash_hmac('sha256', $orderId.'|'.$paymentId, $this->secret);
    }

    /** Test helper: produce a valid webhook signature for a payload. */
    public function signWebhook(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->webhookSecret);
    }
}
