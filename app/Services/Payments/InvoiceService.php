<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Support\Carbon;

/**
 * Generates a GST tax invoice for a captured payment. Idempotent: returns the
 * existing invoice if one was already issued for the payment.
 */
class InvoiceService
{
    public function generateFor(Payment $payment): Invoice
    {
        if ($payment->invoice) {
            return $payment->invoice;
        }

        $tax = (int) $payment->tax_paise;
        $cgst = intdiv($tax, 2);
        $sgst = $tax - $cgst;

        $invoice = new Invoice([
            'user_id' => $payment->user_id,
            'payment_id' => $payment->id,
            'invoice_number' => $this->nextNumber($payment),
            'billing_name' => $payment->user?->name,
            'seller_gstin' => $this->setting('razorpay', 'seller_gstin'),
            'place_of_supply' => 'Kerala',
            'subtotal_paise' => $payment->subtotal_paise,
            'discount_paise' => $payment->discount_paise,
            'tax_paise' => $tax,
            'cgst_paise' => $cgst,
            'sgst_paise' => $sgst,
            'igst_paise' => 0,
            'total_paise' => $payment->amount_paise,
            'currency' => $payment->currency,
            'issued_at' => Carbon::now(),
        ]);
        $invoice->company_id = $payment->company_id;
        $invoice->save();

        return $invoice;
    }

    private function nextNumber(Payment $payment): string
    {
        return sprintf('INV-%s-%06d', Carbon::now()->format('Y'), $payment->id);
    }

    private function setting(string $group, string $key): ?string
    {
        return Setting::query()->where('group', $group)->where('key', $key)->value('value');
    }
}
