<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Payments\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $payments = $request->user()->payments()
            ->with(['plan:id,name', 'invoice:id,uuid,payment_id,invoice_number', 'refunds'])
            ->latest('id')
            ->paginate(15)
            ->through(fn (Payment $p) => [
                'uuid' => $p->uuid,
                'plan' => $p->plan?->name,
                'amount' => $p->amount_paise,
                'status' => $p->status->value,
                'method' => $p->method,
                'created_at' => $p->created_at?->toIso8601String(),
                'paid_at' => $p->paid_at?->toIso8601String(),
                'invoice' => $p->invoice ? ['uuid' => $p->invoice->uuid, 'number' => $p->invoice->invoice_number] : null,
                'refundable' => $p->status->isRefundable() && $p->refundablePaise() > 0,
                'refunded' => $p->refunded_paise,
            ]);

        return Inertia::render('member/payment-history', ['payments' => $payments]);
    }

    public function invoice(Request $request, Invoice $invoice): Response
    {
        abort_unless($invoice->user_id === $request->user()->id, 403);
        $invoice->load('payment.plan');

        return Inertia::render('member/invoice', [
            'invoice' => [
                'number' => $invoice->invoice_number,
                'issued_at' => $invoice->issued_at?->toIso8601String(),
                'billing_name' => $invoice->billing_name,
                'seller_gstin' => $invoice->seller_gstin,
                'place_of_supply' => $invoice->place_of_supply,
                'plan' => $invoice->payment?->plan?->name,
                'subtotal_paise' => $invoice->subtotal_paise,
                'discount_paise' => $invoice->discount_paise,
                'tax_paise' => $invoice->tax_paise,
                'cgst_paise' => $invoice->cgst_paise,
                'sgst_paise' => $invoice->sgst_paise,
                'igst_paise' => $invoice->igst_paise,
                'total_paise' => $invoice->total_paise,
                'currency' => $invoice->currency,
            ],
            'company' => ['name' => config('app.name')],
        ]);
    }

    public function requestRefund(Request $request, Payment $payment, RefundService $refunds): RedirectResponse
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $refunds->request($payment, $request->user(), null, $validated['reason']);

        return back()->with('success', 'Your refund request has been submitted for review.');
    }
}
