<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PlanResource;
use App\Models\SubscriptionPlan;
use App\Services\Payments\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends ApiController
{
    public function plans(): JsonResponse
    {
        return $this->ok(PlanResource::collection(
            SubscriptionPlan::active()->orderBy('sort_order')->get()
        )->resolve());
    }

    public function current(Request $request, EntitlementService $entitlements): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->activeSubscription()?->load('plan');

        return $this->ok([
            'subscription' => $subscription ? [
                'uuid' => $subscription->uuid,
                'plan' => $subscription->plan?->name,
                'status' => $subscription->status->value,
                'ends_at' => $subscription->ends_at?->toIso8601String(),
            ] : null,
            'entitlements' => $entitlements->for($user),
        ]);
    }
}
