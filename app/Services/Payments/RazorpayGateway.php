<?php

namespace App\Services\Payments;

use Razorpay\Api\Api;
use RuntimeException;

/**
 * Live Razorpay implementation. Order creation, payment fetch and refunds use
 * the official SDK; signature verification is done locally with HMAC-SHA256 so
 * it is fast, deterministic and unit-testable.
 */
class RazorpayGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $key,
        private readonly string $secret,
        private readonly ?string $webhookSecret = null,
    ) {}

    public function createOrder(int $amountPaise, string $receipt, array $notes = []): array
    {
        $order = $this->api()->order->create([
            'amount' => $amountPaise,
            'currency' => 'INR',
            'receipt' => $receipt,
            'notes' => $notes,
            'payment_capture' => 1,
        ]);

        return [
            'id' => $order['id'],
            'amount' => (int) $order['amount'],
            'currency' => $order['currency'],
        ];
    }

    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $this->secret);

        return hash_equals($expected, $signature);
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (empty($this->webhookSecret)) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    public function fetchPayment(string $paymentId): array
    {
        return $this->api()->payment->fetch($paymentId)->toArray();
    }

    public function refund(string $paymentId, int $amountPaise): array
    {
        $refund = $this->api()->payment->fetch($paymentId)->refund(['amount' => $amountPaise]);

        return ['id' => $refund['id'], 'status' => $refund['status'] ?? 'processed'];
    }

    public function publicKey(): ?string
    {
        return $this->key;
    }

    public function isLive(): bool
    {
        return true;
    }

    private function api(): Api
    {
        if (! class_exists(Api::class)) {
            throw new RuntimeException('Razorpay SDK is not installed.');
        }

        return new Api($this->key, $this->secret);
    }
}
