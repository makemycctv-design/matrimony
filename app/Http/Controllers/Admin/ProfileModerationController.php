<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProfileStatus;
use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use App\Services\Profile\VerificationService;
use App\Support\Profile\ProfilePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileModerationController extends Controller
{
    public function __construct(private readonly VerificationService $verification) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', MemberProfile::class);

        $filters = $request->only(['status', 'search', 'verified']);

        $profiles = MemberProfile::query()
            ->with('user:id,name,email')
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when(($filters['verified'] ?? null) !== null && $filters['verified'] !== '', function ($q) use ($filters) {
                $q->where('is_verified', filter_var($filters['verified'], FILTER_VALIDATE_BOOL));
            })
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('profile_code', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%"));
                });
            })
            ->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (MemberProfile $p) => [
                'uuid' => $p->uuid,
                'profile_code' => $p->profile_code,
                'name' => $p->user?->name,
                'email' => $p->user?->email,
                'gender' => $p->gender?->value,
                'age' => $p->age,
                'status' => $p->status?->value,
                'is_verified' => $p->is_verified,
                'completion' => $p->completion_percentage,
                'created_at' => $p->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/profiles/index', [
            'profiles' => $profiles,
            'filters' => $filters,
            'statuses' => array_map(fn (ProfileStatus $s) => ['value' => $s->value, 'label' => $s->label()], ProfileStatus::cases()),
        ]);
    }

    public function show(MemberProfile $profile, ProfilePresenter $presenter): Response
    {
        $this->authorize('view', $profile);

        return Inertia::render('admin/profiles/show', [
            'profile' => $presenter->forAdmin($profile),
        ]);
    }

    public function approve(Request $request, MemberProfile $profile): RedirectResponse
    {
        $this->authorize('approve', $profile);

        $validated = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);
        $this->verification->approve($profile, $validated['notes'] ?? null);

        return back()->with('success', "Profile {$profile->profile_code} verified.");
    }

    public function reject(Request $request, MemberProfile $profile): RedirectResponse
    {
        $this->authorize('reject', $profile);

        $validated = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->verification->reject($profile, $validated['reason']);

        return back()->with('success', "Profile {$profile->profile_code} sent back to the member.");
    }

    public function suspend(Request $request, MemberProfile $profile): RedirectResponse
    {
        $this->authorize('suspend', $profile);

        $validated = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $this->verification->suspend($profile, $validated['reason']);

        return back()->with('success', "Profile {$profile->profile_code} suspended.");
    }
}
