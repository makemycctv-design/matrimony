<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\EntitlementService;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymentService;
use App\Services\Payments\SubscriptionService;
use App\Support\Billing\PlanPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkout,
        private readonly PaymentService $payments,
        private readonly SubscriptionService $subscriptions,
        private readonly PaymentGateway $gateway,
    ) {}

    public function index(Request $request, PlanPresenter $presenter, EntitlementService $entitlements): Response
    {
        $user = $request->user();
        $active = $user->activeSubscription()?->load('plan');

        return Inertia::render('member/subscription', [
            'plans' => $presenter->collection(SubscriptionPlan::active()->orderBy('sort_order')->get()),
            'current' => $active ? [
                'uuid' => $active->uuid,
                'plan' => $active->plan?->name,
                'status' => $active->status->value,
                'ends_at' => $active->ends_at?->toIso8601String(),
                'auto_renew' => $active->auto_renew,
            ] : null,
            'entitlements' => $entitlements->for($user),
        ]);
    }

    /** Create a server-side order and hand off to Razorpay Checkout. */
    public function checkout(Request $request, PlanPresenter $presenter): Response|RedirectResponse
    {
        $validated = $request->validate([
            'plan' => ['required', Rule::exists('subscription_plans', 'uuid')],
            'coupon' => ['nullable', 'string', 'max:40'],
        ]);

        $user = $request->user();
        $plan = SubscriptionPlan::where('uuid', $validated['plan'])->firstOrFail();

        $result = $this->checkout->createOrder($user, $plan, $validated['coupon'] ?? null);
        $payment = $result['payment'];

        // Zero-value (free plan or 100% coupon): activate immediately, no gateway.
        if ($payment->amount_paise === 0) {
            $this->payments->capture($payment);

            return redirect()->route('member.subscription.success', ['payment' => $payment->uuid]);
        }

        return Inertia::render('member/checkout', [
            'plan' => $presenter->plan($plan),
            'payment' => ['uuid' => $payment->uuid],
            'order' => [
                'id' => $result['order_id'],
                'key' => $result['key'],
                'amount' => $payment->amount_paise,
                'currency' => $payment->currency,
            ],
            'breakdown' => $result['breakdown'],
            'prefill' => ['name' => $user->name, 'email' => $user->email, 'contact' => $user->mobile],
            // When no live gateway is configured, the UI offers a test flow.
            'simulate' => ! $this->gateway->isLive(),
        ]);
    }

    /**
     * Dev/demo only: complete a payment without a live gateway. Never available
     * in production or when a real Razorpay integration is configured.
     */
    public function simulate(Request $request, Payment $payment): RedirectResponse
    {
        abort_if($this->gateway->isLive() || app()->isProduction(), 403);
        abort_unless($payment->user_id === $request->user()->id, 403);

        if ($request->boolean('fail')) {
            $this->payments->markFailed($payment, 'Simulated failure');

            return redirect()->route('member.subscription.failed', ['payment' => $payment->uuid]);
        }

        $this->payments->capture($payment, 'pay_sim_'.Str::random(10), 'upi');

        return redirect()->route('member.subscription.success', ['payment' => $payment->uuid]);
    }

    /** Razorpay Checkout success handler posts the signed result here. */
    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'payment' => ['required', Rule::exists('payments', 'uuid')],
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $payment = Payment::where('uuid', $validated['payment'])->firstOrFail();
        abort_unless($payment->user_id === $request->user()->id, 403);

        try {
            $this->payments->verifyAndCapture(
                $payment,
                $validated['razorpay_order_id'],
                $validated['razorpay_payment_id'],
                $validated['razorpay_signature'],
            );
        } catch (ValidationException) {
            return redirect()->route('member.subscription.failed', ['payment' => $payment->uuid]);
        }

        return redirect()->route('member.subscription.success', ['payment' => $payment->uuid]);
    }

    /** Called when the member dismisses/fails the Razorpay modal. */
    public function failed(Request $request, Payment $payment): Response
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        if ($request->isMethod('post')) {
            $this->payments->markFailed($payment, 'Payment not completed');

            return Inertia::render('member/payment-failed', ['payment' => ['uuid' => $payment->uuid]]);
        }

        return Inertia::render('member/payment-failed', ['payment' => ['uuid' => $payment->uuid]]);
    }

    public function success(Request $request, Payment $payment): Response
    {
        abort_unless($payment->user_id === $request->user()->id, 403);
        $payment->load('plan', 'invoice');

        return Inertia::render('member/payment-success', [
            'payment' => [
                'uuid' => $payment->uuid,
                'amount' => $payment->amount_paise,
                'plan' => $payment->plan?->name,
                'status' => $payment->status->value,
                'invoice' => $payment->invoice ? ['uuid' => $payment->invoice->uuid, 'number' => $payment->invoice->invoice_number] : null,
            ],
        ]);
    }

    public function cancel(Request $request): RedirectResponse
    {
        $subscription = $request->user()->activeSubscription();

        if ($subscription) {
            $this->subscriptions->cancel($subscription);
        }

        return back()->with('success', 'Auto-renewal cancelled. You keep access until the current period ends.');
    }
}
