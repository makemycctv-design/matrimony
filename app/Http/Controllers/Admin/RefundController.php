<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\Payments\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RefundController extends Controller
{
    public function __construct(private readonly RefundService $refunds) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('refunds.view'), 403);

        $status = $request->input('status', 'all');

        $refunds = Refund::query()
            ->with(['payment:id,uuid,amount_paise,razorpay_payment_id', 'requester:id,name'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Refund $r) => [
                'uuid' => $r->uuid,
                'amount' => $r->amount_paise,
                'status' => $r->status->value,
                'reason' => $r->reason,
                'requested_by' => $r->requester?->name,
                'payment_uuid' => $r->payment?->uuid,
                'created_at' => $r->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/refunds/index', [
            'refunds' => $refunds,
            'filters' => ['status' => $status],
            'statuses' => array_map(fn (RefundStatus $s) => ['value' => $s->value, 'label' => $s->label()], RefundStatus::cases()),
            'canManage' => $request->user()->can('refunds.manage'),
        ]);
    }

    /** Staff-initiated refund against a payment (goes straight to approved). */
    public function store(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($request->user()->can('refunds.manage'), 403);

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:1'], // rupees; null = full refundable
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $amountPaise = isset($validated['amount']) ? (int) round($validated['amount'] * 100) : null;
        $refund = $this->refunds->request($payment, $request->user(), $amountPaise, $validated['reason']);
        $this->refunds->approve($refund, $request->user());

        return back()->with('success', 'Refund created and approved. Process it to complete.');
    }

    public function approve(Request $request, Refund $refund): RedirectResponse
    {
        abort_unless($request->user()->can('refunds.manage'), 403);
        $this->refunds->approve($refund, $request->user());

        return back()->with('success', 'Refund approved.');
    }

    public function reject(Request $request, Refund $refund): RedirectResponse
    {
        abort_unless($request->user()->can('refunds.manage'), 403);
        $validated = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);
        $this->refunds->reject($refund, $request->user(), $validated['notes'] ?? null);

        return back()->with('success', 'Refund rejected.');
    }

    public function process(Request $request, Refund $refund): RedirectResponse
    {
        abort_unless($request->user()->can('refunds.manage'), 403);
        $this->refunds->process($refund, $request->user());

        return back()->with('success', 'Refund processed.');
    }
}
