<?php

namespace App\Events\Interest;

use App\Models\Interest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InterestAccepted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Interest $interest) {}
}
