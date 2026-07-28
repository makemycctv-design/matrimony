<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streaming CSV exports for reports. Streamed row-by-row so large datasets
 * export with a flat memory footprint (no in-memory buffering, no extra
 * dependency). Access is permission gated.
 */
class ExportController extends Controller
{
    public function users(Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('reports.export'), 403);

        return $this->stream('users-'.now()->format('Ymd-His').'.csv',
            ['UUID', 'Name', 'Email', 'Mobile', 'Status', 'Verified', 'Joined'],
            User::query()->with('profile')->cursor(),
            fn (User $u) => [
                $u->uuid,
                $u->name,
                $u->email,
                $u->fullMobile(),
                $u->status?->value,
                $u->profile?->is_verified ? 'yes' : 'no',
                $u->created_at?->toDateTimeString(),
            ],
        );
    }

    public function payments(Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('payments.export'), 403);

        return $this->stream('payments-'.now()->format('Ymd-His').'.csv',
            ['UUID', 'User', 'Plan', 'Amount (INR)', 'Status', 'Method', 'Order ID', 'Paid At'],
            Payment::query()->with(['user:id,name', 'plan:id,name'])->cursor(),
            fn (Payment $p) => [
                $p->uuid,
                $p->user?->name,
                $p->plan?->name,
                number_format($p->amount_paise / 100, 2, '.', ''),
                $p->status->value,
                $p->method,
                $p->razorpay_order_id,
                $p->paid_at?->toDateTimeString(),
            ],
        );
    }

    /**
     * @param  list<string>  $headers
     * @param  iterable<mixed>  $rows
     */
    private function stream(string $filename, array $headers, iterable $rows, callable $map): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows, $map) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $map($row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
