<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caste extends Model
{
    use IsMasterData;

    protected $fillable = [
        'company_id', 'religion_id', 'name', 'slug', 'name_translations', 'sort_order', 'is_active',
    ];

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Religion::class);
    }

    public function subCastes(): HasMany
    {
        return $this->hasMany(SubCaste::class);
    }
}
