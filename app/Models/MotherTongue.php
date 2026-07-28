<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Model;

class MotherTongue extends Model
{
    use IsMasterData;

    protected $fillable = [
        'company_id', 'name', 'slug', 'name_translations', 'sort_order', 'is_active',
    ];
}
