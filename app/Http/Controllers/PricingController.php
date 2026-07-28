<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Support\Billing\PlanPresenter;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PricingController extends Controller
{
    /** Public pricing + plan comparison page (SEO-friendly, cached). */
    public function index(PlanPresenter $presenter): Response
    {
        $plans = Cache::remember('pricing.plans', now()->addMinutes(30), function () use ($presenter) {
            return $presenter->collection(
                SubscriptionPlan::active()->orderBy('sort_order')->get(),
            );
        });

        return Inertia::render('pricing', [
            'plans' => $plans,
            'seo' => [
                'title' => config('app.name').' — Membership Plans & Pricing',
                'description' => 'Simple, transparent matrimony membership plans. Start free and upgrade to connect.',
            ],
        ]);
    }
}
