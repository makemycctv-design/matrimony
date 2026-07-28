<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ProfileDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ProfileDocument extends Model
{
    /** @use HasFactory<ProfileDocumentFactory> */
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $guarded = ['id', 'uuid', 'company_id', 'status', 'reviewed_by', 'reviewed_at'];

    protected $hidden = ['document_number', 'path'];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'status' => DocumentStatus::class,
            'type' => DocumentType::class,
            // Government ID numbers are encrypted at rest and never logged.
            'document_number' => 'encrypted',
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ProfileDocument $document): void {
            $document->uuid ??= (string) Str::ulid();
        });

        static::deleting(function (ProfileDocument $document): void {
            if ($document->isForceDeleting() && Storage::disk($document->disk)->exists($document->path)) {
                Storage::disk($document->disk)->delete($document->path);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class, 'member_profile_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** A short-lived signed URL granting temporary access to the raw document. */
    public function signedUrl(int $minutes = 10): string
    {
        return URL::temporarySignedRoute(
            'media.document',
            now()->addMinutes($minutes),
            ['document' => $this->uuid],
        );
    }
}
