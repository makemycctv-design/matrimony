<?php

namespace App\Events\Profile;

use App\Models\MemberProfile;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProfileVerified
{
    use Dispatchable, SerializesModels;

    public function __construct(public MemberProfile $profile) {}
}
