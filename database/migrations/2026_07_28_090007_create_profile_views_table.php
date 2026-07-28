<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks who viewed whom. One row per (viewer, viewed) pair with a rolling
 * count + last-viewed timestamp, powering "recently viewed" and view counters
 * without unbounded growth.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('viewer_profile_id')->constrained('member_profiles')->cascadeOnDelete();
            $table->foreignId('viewed_profile_id')->constrained('member_profiles')->cascadeOnDelete();
            $table->unsignedInteger('view_count')->default(1);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            $table->unique(['viewer_profile_id', 'viewed_profile_id'], 'view_unique');
            $table->index(['viewed_profile_id', 'last_viewed_at']);
            $table->index(['viewer_profile_id', 'last_viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_views');
    }
};
