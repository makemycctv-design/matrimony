<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogger;
use App\Services\Matching\MatchingSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin configuration of the global compatibility weights that drive the
 * match-scoring algorithm.
 */
class MatchingSettingsController extends Controller
{
    public function __construct(
        private readonly MatchingSettingsService $settings,
        private readonly AuditLogger $audit,
    ) {}

    public function edit(Request $request): Response
    {
        abort_unless($request->user()->can('matching.configure'), 403);

        return Inertia::render('admin/matching-settings', [
            'weights' => $this->settings->weights(),
            'defaults' => MatchingSettingsService::DEFAULT_WEIGHTS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('matching.configure'), 403);

        $factors = array_keys(MatchingSettingsService::DEFAULT_WEIGHTS);

        $validated = $request->validate([
            'weights' => ['required', 'array'],
            'weights.*' => ['integer', 'min:0', 'max:100'],
        ]);

        $weights = array_intersect_key($validated['weights'], array_flip($factors));

        $this->settings->update($weights);
        $this->audit->log('matching.weights_updated', null, 'Updated matching weights', null, $weights);

        return back()->with('success', 'Matching weights updated.');
    }
}
