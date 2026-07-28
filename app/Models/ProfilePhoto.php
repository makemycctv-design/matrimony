<?php

namespace App\Models;

use App\Enums\PhotoStatus;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ProfilePhotoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfilePhoto extends Model
{
    /** @use HasFactory<ProfilePhotoFactory> */
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $guarded = ['id', 'uuid', 'company_id', 'status', 'moderated_by', 'moderated_at'];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'moderated_at' => 'datetime',
            'status' => PhotoStatus::class,
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ProfilePhoto $photo): void {
            $photo->uuid ??= (string) Str::ulid();
        });

        // Clean up files from disk when a photo is permanently removed.
        static::deleting(function (ProfilePhoto $photo): void {
            if ($photo->isForceDeleting()) {
                $photo->deleteFiles();
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

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', PhotoStatus::Approved->value);
    }

    public function isApproved(): bool
    {
        return $this->status === PhotoStatus::Approved;
    }

    public function url(): string
    {
        return route('media.photo', ['photo' => $this->uuid]);
    }

    public function thumbUrl(): string
    {
        return $this->thumbnail_path
            ? route('media.photo', ['photo' => $this->uuid, 'variant' => 'thumb'])
            : $this->url();
    }

    public function deleteFiles(): void
    {
        $disk = Storage::disk($this->disk);

        foreach (array_filter([$this->path, $this->thumbnail_path]) as $file) {
            if ($disk->exists($file)) {
                $disk->delete($file);
            }
        }
    }
}
