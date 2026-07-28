<?php

namespace App\Services\Payments;

use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Creates and manages member subscriptions and their entitlements. New paid
 * periods stack on top of any remaining time so members never lose paid days.
 */
class SubscriptionService
{
    /** Activate a subscription from a captured payment. */
    public function activateFromPayment(Payment $payment): Subscription
    {
        $plan = $payment->plan;
        $user = $payment->user;

        $subscription = $this->start($user, $plan, source: 'razorpay', price: $payment->amount_paise);
        $payment->forceFill(['subscription_id' => $subscription->id])->save();

        return $subscription;
    }

    /** Start (or extend) a subscription for a user on a plan. */
    public function start(User $user, SubscriptionPlan $plan, string $source = 'manual', ?int $price = null): Subscription
    {
        $current = $user->activeSubscription();
        $from = $current && $current->ends_at && $current->ends_at->isFuture()
            ? $current->ends_at
            : Carbon::now();

        $subscription = new Subscription([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'price_paise' => $price ?? $plan->price_paise,
            'entitlements' => $plan->entitlements(),
            'starts_at' => Carbon::now(),
            'ends_at' => $from->copy()->addDays($plan->duration_days),
            'source' => $source,
        ]);
        $subscription->company_id = $user->company_id;
        $subscription->save();

        // Premium role bridges subscription state to visibility/permission gating.
        if (! $plan->isFree() && ! $user->hasRole('Premium Member')) {
            $user->assignRole('Premium Member');
        }

        return $subscription;
    }

    public function cancel(Subscription $subscription): void
    {
        $subscription->forceFill([
            'status' => SubscriptionStatus::Cancelled,
            'auto_renew' => false,
            'cancelled_at' => Carbon::now(),
        ])->save();

        $this->syncPremiumRole($subscription->user);
    }

    /** Mark lapsed subscriptions expired and revoke premium where appropriate. */
    public function expireDue(): int
    {
        $expired = 0;

        Subscription::query()
            ->where('status', SubscriptionStatus::Active->value)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', Carbon::now())
            ->with('user')
            ->chunkById(200, function ($subs) use (&$expired) {
                foreach ($subs as $sub) {
                    $sub->forceFill(['status' => SubscriptionStatus::Expired->value])->save();
                    $this->syncPremiumRole($sub->user);
                    $expired++;
                }
            });

        return $expired;
    }

    /** Remove the premium role if the user has no remaining usable subscription. */
    private function syncPremiumRole(?User $user): void
    {
        if ($user === null) {
            return;
        }

        if ($user->activeSubscription() === null && $user->hasRole('Premium Member')) {
            $user->removeRole('Premium Member');
        }
    }
}
