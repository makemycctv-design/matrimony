<?php

namespace App\Services\Payments;

/**
 * Abstraction over the payment provider so the platform is testable offline and
 * provider-swappable. Signature verification is implemented locally (HMAC) and
 * never requires a network call; order/refund/fetch talk to the provider.
 */
interface PaymentGateway
{
    /**
     * Create a provider order for the given amount (in paise).
     *
     * @param  array<string, mixed>  $notes
     * @return array{id:string, amount:int, currency:string}
     */
    public function createOrder(int $amountPaise, string $receipt, array $notes = []): array;

    /** Verify the Checkout callback signature (order_id|payment_id, HMAC-SHA256). */
    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool;

    /** Verify an inbound webhook payload signature. */
    public function verifyWebhookSignature(string $payload, string $signature): bool;

    /**
     * Fetch authoritative payment details from the provider.
     *
     * @return array<string, mixed>
     */
    public function fetchPayment(string $paymentId): array;

    /**
     * Issue a (possibly partial) refund for a captured payment.
     *
     * @return array{id:string, status:string}
     */
    public function refund(string $paymentId, int $amountPaise): array;

    /** The public key id safe to expose to the browser Checkout. */
    public function publicKey(): ?string;

    public function isLive(): bool;
}
