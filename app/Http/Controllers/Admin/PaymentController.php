<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('payments.view'), 403);

        $status = $request->input('status', 'all');
        $search = $request->input('search');

        $payments = Payment::query()
            ->with(['user:id,name,email', 'plan:id,name'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q, $s) => $q->where(function ($sub) use ($s) {
                $sub->where('razorpay_payment_id', 'like', "%{$s}%")
                    ->orWhere('razorpay_order_id', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$s}%"));
            }))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Payment $p) => [
                'uuid' => $p->uuid,
                'user' => $p->user?->name,
                'email' => $p->user?->email,
                'plan' => $p->plan?->name,
                'amount' => $p->amount_paise,
                'refunded' => $p->refunded_paise,
                'status' => $p->status->value,
                'method' => $p->method,
                'verified' => $p->signature_verified,
                'created_at' => $p->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/payments/index', [
            'payments' => $payments,
            'filters' => ['status' => $status, 'search' => $search],
            'statuses' => array_map(fn (PaymentStatus $s) => ['value' => $s->value, 'label' => $s->label()], PaymentStatus::cases()),
            'totals' => [
                'captured_paise' => (int) Payment::where('status', PaymentStatus::Captured->value)->sum('amount_paise'),
                'refunded_paise' => (int) Payment::sum('refunded_paise'),
            ],
        ]);
    }

    public function show(Request $request, Payment $payment): Response
    {
        abort_unless($request->user()->can('payments.view'), 403);

        $payment->load(['user:id,name,email', 'plan:id,name', 'coupon:id,code', 'invoice', 'refunds.requester:id,name', 'subscription']);

        return Inertia::render('admin/payments/show', [
            'payment' => [
                'uuid' => $payment->uuid,
                'user' => $payment->user?->name,
                'email' => $payment->user?->email,
                'plan' => $payment->plan?->name,
                'status' => $payment->status->value,
                'method' => $payment->method,
                'razorpay_order_id' => $payment->razorpay_order_id,
                'razorpay_payment_id' => $payment->razorpay_payment_id,
                'signature_verified' => $payment->signature_verified,
                'subtotal_paise' => $payment->subtotal_paise,
                'discount_paise' => $payment->discount_paise,
                'tax_paise' => $payment->tax_paise,
                'amount_paise' => $payment->amount_paise,
                'refunded_paise' => $payment->refunded_paise,
                'refundable_paise' => $payment->refundablePaise(),
                'coupon' => $payment->coupon?->code,
                'invoice' => $payment->invoice?->invoice_number,
                'created_at' => $payment->created_at?->toIso8601String(),
                'paid_at' => $payment->paid_at?->toIso8601String(),
                'refunds' => $payment->refunds->map(fn ($r) => [
                    'uuid' => $r->uuid,
                    'amount' => $r->amount_paise,
                    'status' => $r->status->value,
                    'reason' => $r->reason,
                    'requested_by' => $r->requester?->name,
                ]),
            ],
            'canRefund' => $request->user()->can('refunds.manage'),
        ]);
    }
}
