<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProfileStatus;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        return Inertia::render('admin/dashboard', [
            'metrics' => [
                'total_users' => User::query()->count(),
                'new_today' => User::query()->whereDate('created_at', $today)->count(),
                'new_this_month' => User::query()->where('created_at', '>=', $monthStart)->count(),
                'verified_profiles' => MemberProfile::query()->where('is_verified', true)->count(),
                'pending_reviews' => MemberProfile::query()
                    ->whereIn('status', [ProfileStatus::Submitted->value, ProfileStatus::UnderReview->value])
                    ->count(),
                'suspended' => User::query()->where('status', UserStatus::Suspended->value)->count(),
                'active_subscriptions' => 0, // Wired to subscriptions in Phase 4.
                'revenue_today' => 0,
                'revenue_month' => 0,
                'failed_payments' => 0,
                'open_reports' => 0,
            ],
            'registrationTrend' => $this->registrationTrend(),
            'recentRegistrations' => User::query()
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (User $u) => [
                    'uuid' => $u->uuid,
                    'name' => $u->name,
                    'email' => $u->email,
                    'status' => $u->status?->value,
                    'created_at' => $u->created_at?->toIso8601String(),
                ]),
            'recentAdminActions' => AuditLog::query()
                ->with('user:id,name')
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn (AuditLog $log) => [
                    'action' => $log->action,
                    'description' => $log->description,
                    'by' => $log->user?->name,
                    'at' => $log->created_at?->toIso8601String(),
                ]),
        ]);
    }

    /**
     * Registrations for the last 14 days for the dashboard chart.
     *
     * @return list<array{date:string,count:int}>
     */
    private function registrationTrend(): array
    {
        $days = collect(range(13, 0))->map(function (int $offset) {
            $date = Carbon::today()->subDays($offset);

            return [
                'date' => $date->format('d M'),
                'count' => User::query()->whereDate('created_at', $date)->count(),
            ];
        });

        return $days->all();
    }
}
