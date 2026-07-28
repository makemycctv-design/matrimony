<?php

namespace App\Http\Controllers;

use App\Services\Payments\PaymentGateway;
use App\Services\Payments\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Authoritative Razorpay webhook receiver. Verifies the signature against the
 * RAW request body (not the parsed array) and delegates to WebhookService,
 * which enforces replay/idempotency via the unique provider event id.
 */
class WebhookController extends Controller
{
    public function razorpay(Request $request, PaymentGateway $gateway, WebhookService $webhooks): JsonResponse
    {
        $raw = $request->getContent();
        $signature = (string) $request->header('X-Razorpay-Signature', '');
        $eventId = (string) $request->header('X-Razorpay-Event-Id', (string) Str::uuid());

        $valid = $signature !== '' && $gateway->verifyWebhookSignature($raw, $signature);

        $payload = (array) $request->json()->all();
        $eventType = (string) ($payload['event'] ?? 'unknown');

        $event = $webhooks->handle($eventId, $eventType, $payload, $valid);

        // Always acknowledge receipt; invalid-signature events are recorded and
        // reported so the provider does not retry indefinitely.
        return response()->json([
            'received' => true,
            'status' => $event->status,
        ], $valid ? 200 : 202);
    }
}
