<?php

namespace App\Events\Profile;

use App\Models\ProfilePhoto;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProfilePhotoModerated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ProfilePhoto $photo,
        public bool $approved,
        public ?string $reason = null,
    ) {}
}
