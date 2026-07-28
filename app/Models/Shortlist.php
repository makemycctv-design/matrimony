<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shortlist extends Model
{
    use BelongsToCompany;

    protected $guarded = ['id', 'company_id'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'member_profile_id');
    }

    public function shortlisted(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'shortlisted_profile_id');
    }
}
