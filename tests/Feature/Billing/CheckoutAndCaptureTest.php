<?php

namespace Tests\Feature\Billing;

use App\Enums\PaymentStatus;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\FakePaymentGateway;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CheckoutAndCaptureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::factory()->create([
            'price_paise' => 100000, 'gst_percent' => 18, 'duration_days' => 30, 'is_active' => true,
        ]);
    }

    public function test_checkout_creates_an_order_with_gst(): void
    {
        $user = User::factory()->create();
        $result = app(CheckoutService::class)->createOrder($user, $this->plan());

        $this->assertNotNull($result['order_id']);
        $this->assertSame(100000, $result['payment']->subtotal_paise);
        $this->assertSame(18000, $result['payment']->tax_paise);
        $this->assertSame(118000, $result['payment']->amount_paise);
        $this->assertSame(PaymentStatus::Created, $result['payment']->status);
    }

    public function test_valid_signature_captures_payment_and_activates_subscription(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $plan = $this->plan();

        $result = app(CheckoutService::class)->createOrder($user, $plan);
        $payment = $result['payment'];

        /** @var FakePaymentGateway $gw */
        $gw = app(PaymentGateway::class);
        $signature = $gw->signPayment($result['order_id'], 'pay_TEST');

        app(PaymentService::class)->verifyAndCapture($payment, $result['order_id'], 'pay_TEST', $signature);

        $payment->refresh();
        $this->assertSame(PaymentStatus::Captured, $payment->status);
        $this->assertTrue($payment->signature_verified);
        $this->assertNotNull($payment->invoice);
        $this->assertNotNull($user->fresh()->activeSubscription());
        $this->assertTrue($user->fresh()->hasRole('Premium Member'));
    }

    public function test_invalid_signature_fails_the_payment(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $result = app(CheckoutService::class)->createOrder($user, $this->plan());

        $this->expectException(ValidationException::class);

        try {
            app(PaymentService::class)->verifyAndCapture($result['payment'], $result['order_id'], 'pay_X', 'bad_signature');
        } finally {
            $this->assertSame(PaymentStatus::Failed, $result['payment']->fresh()->status);
            $this->assertNull($user->fresh()->activeSubscription());
        }
    }

    public function test_capture_is_idempotent(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $result = app(CheckoutService::class)->createOrder($user, $this->plan());
        $service = app(PaymentService::class);

        $service->capture($result['payment']);
        $service->capture($result['payment']->fresh());

        // Only one subscription should have been created despite two captures.
        $this->assertSame(1, $user->fresh()->subscriptions()->count());
    }
}
