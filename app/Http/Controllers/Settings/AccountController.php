<?php

namespace App\Http\Controllers\Settings;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Member-facing account lifecycle: reversible deactivation and a
 * grace-period deletion request workflow (never an instant hard delete).
 */
class AccountController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** Temporarily hide the account; the member can log back in to reactivate. */
    public function deactivate(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();
        $user->forceFill(['status' => UserStatus::Deactivated])->save();
        $user->profile?->forceFill(['status' => 'deactivated'])->save();

        $this->audit->log('account.deactivated', $user, 'Member self-deactivated their account');

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Your account has been deactivated.');
    }

    /** Submit a deletion request; data is purged after a configurable grace period. */
    public function requestDeletion(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'current_password'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        $graceDays = (int) config('app.deletion_grace_days', 14);

        AccountDeletionRequest::updateOrCreate(
            ['user_id' => $user->id, 'status' => 'pending'],
            [
                'reason' => $validated['reason'] ?? null,
                'requested_at' => Carbon::now(),
                'scheduled_for' => Carbon::now()->addDays($graceDays),
            ],
        );

        $user->forceFill(['status' => UserStatus::Deactivated])->save();

        $this->audit->log('account.deletion_requested', $user, "Deletion requested (grace: {$graceDays} days)");

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', "Your account is scheduled for deletion in {$graceDays} days. Log in before then to cancel.");
    }

    /** Cancel a pending deletion (called after logging back in). */
    public function cancelDeletion(Request $request): RedirectResponse
    {
        $user = $request->user();

        AccountDeletionRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->update(['status' => 'cancelled', 'processed_at' => Carbon::now()]);

        $user->forceFill(['status' => UserStatus::Active])->save();

        $this->audit->log('account.deletion_cancelled', $user, 'Member cancelled pending deletion');

        return back()->with('status', 'Your account deletion request has been cancelled.');
    }
}
