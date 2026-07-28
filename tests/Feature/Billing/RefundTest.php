<?php

namespace Tests\Feature\Billing;

use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\Payment\RefundProcessedNotification;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\PaymentService;
use App\Services\Payments\RefundService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RefundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        Notification::fake();
    }

    private function capturedPayment(): Payment
    {
        $user = User::factory()->create();
        $plan = SubscriptionPlan::factory()->create(['price_paise' => 100000, 'gst_percent' => 18]);
        $result = app(CheckoutService::class)->createOrder($user, $plan);
        app(PaymentService::class)->capture($result['payment'], 'pay_1', 'upi');

        return $result['payment']->fresh();
    }

    public function test_full_refund_lifecycle(): void
    {
        $payment = $this->capturedPayment();
        $staff = User::factory()->create();
        $staff->assignRole('Finance Staff');

        $service = app(RefundService::class);
        $refund = $service->request($payment, $payment->user, null, 'Changed my mind');
        $service->approve($refund, $staff);
        $service->process($refund, $staff);

        $payment->refresh();
        $this->assertSame(RefundStatus::Processed, $refund->fresh()->status);
        $this->assertSame($payment->amount_paise, $payment->refunded_paise);
        $this->assertSame(PaymentStatus::Refunded, $payment->status);
        Notification::assertSentTo($payment->user, RefundProcessedNotification::class);
    }

    public function test_cannot_refund_more_than_paid(): void
    {
        $payment = $this->capturedPayment();

        $this->expectException(ValidationException::class);
        app(RefundService::class)->request($payment, $payment->user, $payment->amount_paise + 100, 'Too much');
    }

    public function test_member_can_request_refund_via_http(): void
    {
        $payment = $this->capturedPayment();

        $this->actingAs($payment->user)
            ->post(route('member.billing.refund', ['payment' => $payment->uuid]), ['reason' => 'Please refund me'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('refunds', ['payment_id' => $payment->id, 'status' => 'requested']);
    }
}
