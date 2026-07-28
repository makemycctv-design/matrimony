<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
    ) {}

    public function edit(Request $request): Response
    {
        abort_unless($request->user()->can('settings.manage'), 403);

        return Inertia::render('admin/settings', [
            'settings' => $this->settings->grouped(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings.manage'), 403);

        $validated = $request->validate([
            'settings' => ['required', 'array'],
        ]);

        $this->settings->updateMany($validated['settings']);
        // Do not log secret values — only that settings changed.
        $this->audit->log('settings.updated', null, 'Platform settings updated', null, ['groups' => array_keys($validated['settings'])]);

        return back()->with('success', 'Settings saved.');
    }
}
