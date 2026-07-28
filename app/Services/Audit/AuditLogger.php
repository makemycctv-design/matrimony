<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Support\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Records sensitive staff/admin actions to the dedicated audit_logs table.
 *
 * Never pass secrets (passwords, tokens, ID document contents, payment keys)
 * into $old / $new; callers are responsible for redaction.
 */
class AuditLogger
{
    public function __construct(private readonly TenantManager $tenants) {}

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        ?array $old = null,
        ?array $new = null,
    ): AuditLog {
        return AuditLog::create([
            'company_id' => $this->tenants->id() ?? $subject?->company_id ?? null,
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $subject ? $subject::class : null,
            'auditable_id' => $subject?->getKey(),
            'description' => $description,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 255),
        ]);
    }
}
