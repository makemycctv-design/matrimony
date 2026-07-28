<?php

namespace App\Events\Interest;

use App\Models\Interest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InterestDeclined
{
    use Dispatchable, SerializesModels;

    public function __construct(public Interest $interest) {}
}
