<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\PlanRequest;
use App\Models\SubscriptionPlan;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): Response
    {
        $this->authorizeManage();

        return Inertia::render('admin/plans/index', [
            'plans' => SubscriptionPlan::withCount(['subscriptions' => fn ($q) => $q->active()])
                ->orderBy('sort_order')
                ->get()
                ->map(fn (SubscriptionPlan $p) => [
                    'uuid' => $p->uuid,
                    'name' => $p->name,
                    'tier' => $p->tier,
                    'description' => $p->description,
                    'price' => $p->price_paise / 100,
                    'gst_percent' => (float) $p->gst_percent,
                    'duration_days' => $p->duration_days,
                    'trial_days' => $p->trial_days,
                    'contact_view_access' => $p->contact_view_access,
                    'messaging_access' => $p->messaging_access,
                    'advanced_search' => $p->advanced_search,
                    'profile_boost' => $p->profile_boost,
                    'profile_highlight' => $p->profile_highlight,
                    'verification_priority' => $p->verification_priority,
                    'max_interests_per_day' => $p->max_interests_per_day,
                    'max_profile_views_per_day' => $p->max_profile_views_per_day,
                    'features' => $p->features ?? [],
                    'is_active' => $p->is_active,
                    'is_featured' => $p->is_featured,
                    'sort_order' => $p->sort_order,
                    'active_subscribers' => $p->subscriptions_count,
                ]),
        ]);
    }

    public function store(PlanRequest $request): RedirectResponse
    {
        $plan = SubscriptionPlan::create($request->toAttributes());
        $this->audit->log('plan.created', $plan, "Plan {$plan->name} created");
        Cache::forget('pricing.plans');

        return back()->with('success', 'Plan created.');
    }

    public function update(PlanRequest $request, SubscriptionPlan $plan): RedirectResponse
    {
        $plan->update($request->toAttributes());
        $this->audit->log('plan.updated', $plan, "Plan {$plan->name} updated");
        Cache::forget('pricing.plans');

        return back()->with('success', 'Plan updated.');
    }

    public function destroy(SubscriptionPlan $plan): RedirectResponse
    {
        $this->authorizeManage();
        $plan->delete(); // soft delete; existing subscriptions keep their snapshot
        $this->audit->log('plan.archived', $plan, "Plan {$plan->name} archived");
        Cache::forget('pricing.plans');

        return back()->with('success', 'Plan archived.');
    }

    private function authorizeManage(): void
    {
        abort_unless(request()->user()?->can('plans.manage'), 403);
    }
}
