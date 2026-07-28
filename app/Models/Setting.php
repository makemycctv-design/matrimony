<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'company_id', 'group', 'key', 'value', 'type', 'is_secret',
    ];

    protected function casts(): array
    {
        return ['is_secret' => 'boolean'];
    }

    /** Decode the stored value according to its declared type. */
    public function typedValue(): mixed
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOL),
            'json' => json_decode((string) $this->value, true),
            default => $this->value,
        };
    }
}
