<?php

namespace App\Models;

use App\Models\Concerns\IsMasterData;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    use IsMasterData;

    protected $table = 'educations';

    protected $fillable = [
        'company_id', 'name', 'slug', 'category', 'name_translations', 'sort_order', 'is_active',
    ];
}
