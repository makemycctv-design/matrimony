<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubCaste extends Model
{
    use IsMasterData;

    protected $fillable = [
        'company_id', 'caste_id', 'name', 'slug', 'name_translations', 'sort_order', 'is_active',
    ];

    public function caste(): BelongsTo
    {
        return $this->belongsTo(Caste::class);
    }
}
