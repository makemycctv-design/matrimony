<?php

namespace Tests\Feature\Billing;

use App\Enums\PaymentStatus;
use App\Models\PaymentWebhookEvent;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\PaymentGateway;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Notification::fake();
    }

    private function createdPayment(): array
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['price_paise' => 100000, 'gst_percent' => 18]);
        $result = app(CheckoutService::class)->createOrder($user, $plan);

        return [$result['payment'], $result['order_id']];
    }

    private function sendWebhook(string $eventId, array $payload, ?string $signature = null): TestResponse
    {
        /** @var FakePaymentGateway $gw */
        $gw = app(PaymentGateway::class);
        $content = json_encode($payload);
        $signature ??= $gw->signWebhook($content);

        return $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_RAZORPAY_SIGNATURE' => $signature,
            'HTTP_X_RAZORPAY_EVENT_ID' => $eventId,
        ], $content);
    }

    public function test_captured_webhook_marks_payment_captured(): void
    {
        [$payment, $orderId] = $this->createdPayment();

        $this->sendWebhook('evt_capture_1', [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['order_id' => $orderId, 'id' => 'pay_1', 'method' => 'upi']]],
        ])->assertOk();

        $this->assertSame(PaymentStatus::Captured, $payment->fresh()->status);
        $this->assertDatabaseHas('payment_webhook_events', ['event_id' => 'evt_capture_1', 'status' => 'processed']);
    }

    public function test_duplicate_webhook_is_not_processed_twice(): void
    {
        [$payment, $orderId] = $this->createdPayment();
        $payload = [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['order_id' => $orderId, 'id' => 'pay_1', 'method' => 'upi']]],
        ];

        $this->sendWebhook('evt_dup', $payload)->assertOk();
        $this->sendWebhook('evt_dup', $payload)->assertOk(); // replay

        $this->assertSame(1, PaymentWebhookEvent::where('event_id', 'evt_dup')->count());
        $this->assertSame(1, $payment->user->subscriptions()->count());
    }

    public function test_invalid_signature_is_rejected_and_recorded(): void
    {
        [$payment, $orderId] = $this->createdPayment();

        $response = $this->sendWebhook('evt_bad', [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['order_id' => $orderId, 'id' => 'pay_1']]],
        ], 'invalid');

        $response->assertStatus(202);
        $this->assertDatabaseHas('payment_webhook_events', ['event_id' => 'evt_bad', 'status' => 'failed']);
        $this->assertNotSame(PaymentStatus::Captured, $payment->fresh()->status);
    }
}
