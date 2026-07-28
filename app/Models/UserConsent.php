<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConsent extends Model
{
    protected $fillable = [
        'user_id', 'type', 'version', 'granted', 'ip_address', 'user_agent', 'granted_at',
    ];

    protected function casts(): array
    {
        return [
            'granted' => 'boolean',
            'granted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
