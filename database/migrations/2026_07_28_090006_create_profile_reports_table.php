<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Abuse / misuse reports raised by members against a profile, worked by staff
 * through a moderation queue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_reports', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('reporter_profile_id')->constrained('member_profiles')->cascadeOnDelete();
            $table->foreignId('reported_profile_id')->constrained('member_profiles')->cascadeOnDelete();

            $table->string('reason', 40);
            $table->text('details')->nullable();
            // pending | reviewing | actioned | dismissed
            $table->string('status', 12)->default('pending');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('reported_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_reports');
    }
};
