<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AccountDeletionRequest extends Model
{
    protected $fillable = [
        'user_id', 'reason', 'status', 'requested_at', 'scheduled_for',
        'processed_at', 'processed_by', 'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'scheduled_for' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AccountDeletionRequest $request): void {
            $request->uuid ??= (string) Str::ulid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
