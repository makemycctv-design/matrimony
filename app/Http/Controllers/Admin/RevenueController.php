<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class RevenueController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('reports.view'), 403);

        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        // Fresh query builder per aggregate (each call is independent).
        $captured = fn () => Payment::where('status', PaymentStatus::Captured->value);

        return Inertia::render('admin/revenue', [
            'metrics' => [
                'revenue_today' => (int) $captured()->whereDate('paid_at', $today)->sum('amount_paise'),
                'revenue_month' => (int) $captured()->where('paid_at', '>=', $monthStart)->sum('amount_paise'),
                'revenue_total' => (int) $captured()->sum('amount_paise'),
                'refunded_total' => (int) Payment::sum('refunded_paise'),
                'active_subscriptions' => Subscription::active()->count(),
                'expired_subscriptions' => Subscription::where('status', SubscriptionStatus::Expired->value)->count(),
                'failed_payments' => Payment::where('status', PaymentStatus::Failed->value)->count(),
                'paying_members' => Subscription::active()->distinct('user_id')->count('user_id'),
            ],
            'trend' => $this->revenueTrend(),
            'byPlan' => $this->revenueByPlan(),
        ]);
    }

    /**
     * @return list<array{date:string, amount:int}>
     */
    private function revenueTrend(): array
    {
        return collect(range(29, 0))->map(function (int $offset) {
            $date = Carbon::today()->subDays($offset);

            return [
                'date' => $date->format('d M'),
                'amount' => (int) Payment::where('status', PaymentStatus::Captured->value)
                    ->whereDate('paid_at', $date)
                    ->sum('amount_paise'),
            ];
        })->all();
    }

    /**
     * @return list<array{plan:string, amount:int, count:int}>
     */
    private function revenueByPlan(): array
    {
        return Payment::query()
            ->where('payments.status', PaymentStatus::Captured->value)
            ->join('subscription_plans', 'subscription_plans.id', '=', 'payments.subscription_plan_id')
            ->groupBy('subscription_plans.name')
            ->selectRaw('subscription_plans.name as plan, SUM(payments.amount_paise) as amount, COUNT(*) as count')
            ->get()
            ->map(fn ($r) => ['plan' => $r->plan, 'amount' => (int) $r->amount, 'count' => (int) $r->count])
            ->all();
    }
}
