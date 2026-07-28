<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Member photo gallery. Files live on a private disk and are served through an
 * authorized route; only approved photos are ever shown to other members and
 * even then subject to the owner's photo_visibility preference.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_photos', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('member_profile_id')->constrained('member_profiles')->cascadeOnDelete();

            $table->string('disk')->default('private');
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();

            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            // pending | approved | rejected
            $table->string('status', 12)->default('pending');
            $table->string('moderation_reason')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['member_profile_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['member_profile_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_photos');
    }
};
