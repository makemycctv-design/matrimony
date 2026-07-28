<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\ProfileReport;
use App\Services\Audit\AuditLogger;
use App\Services\Profile\VerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Staff queue for abuse/misuse reports. Handling a report can optionally
 * suspend the reported profile, with the whole action captured in the audit log.
 */
class ReportModerationController extends Controller
{
    public function __construct(
        private readonly VerificationService $verification,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('abuse_reports.view'), 403);

        $status = $request->input('status', 'pending');

        $reports = ProfileReport::query()
            ->with(['reporter:id,uuid,profile_code,first_name', 'reported:id,uuid,profile_code,first_name', 'handler:id,name'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (ProfileReport $r) => [
                'uuid' => $r->uuid,
                'reason' => $r->reason?->value,
                'reason_label' => $r->reason?->label(),
                'details' => $r->details,
                'status' => $r->status?->value,
                'reporter_code' => $r->reporter?->profile_code,
                'reported_code' => $r->reported?->profile_code,
                'reported_uuid' => $r->reported?->uuid,
                'handler' => $r->handler?->name,
                'created_at' => $r->created_at?->toIso8601String(),
                'resolution_notes' => $r->resolution_notes,
            ]);

        return Inertia::render('admin/reports/index', [
            'reports' => $reports,
            'filters' => ['status' => $status],
            'statuses' => array_map(fn (ReportStatus $s) => ['value' => $s->value, 'label' => $s->label()], ReportStatus::cases()),
            'reasons' => ReportReason::options(),
        ]);
    }

    public function update(Request $request, ProfileReport $report): RedirectResponse
    {
        abort_unless($request->user()->can('abuse_reports.manage'), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['reviewing', 'actioned', 'dismissed'])],
            'resolution_notes' => ['nullable', 'string', 'max:1000'],
            'suspend_reported' => ['nullable', 'boolean'],
        ]);

        $report->forceFill([
            'status' => $validated['status'],
            'resolution_notes' => $validated['resolution_notes'] ?? null,
            'handled_by' => $request->user()->id,
            'handled_at' => Carbon::now(),
        ])->save();

        if (! empty($validated['suspend_reported']) && $request->user()->can('profiles.suspend') && $report->reported) {
            $this->verification->suspend($report->reported, 'Suspended following abuse report '.$report->uuid);
        }

        $this->audit->log('report.handled', $report, "Report {$report->uuid} marked {$validated['status']}");

        return back()->with('success', 'Report updated.');
    }
}
