<?php

namespace App\Http\Controllers\Member;

use App\Enums\ReportReason;
use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use App\Services\Interaction\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'profile' => ['required', Rule::exists('member_profiles', 'uuid')],
            'reason' => ['required', new Enum(ReportReason::class)],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);

        $viewer = $request->user()->ensureProfile();
        $target = MemberProfile::where('uuid', $validated['profile'])->firstOrFail();

        $this->reports->report($viewer, $target, $validated['reason'], $validated['details'] ?? null);

        return back()->with('success', 'Thank you. Our team will review this report.');
    }
}
