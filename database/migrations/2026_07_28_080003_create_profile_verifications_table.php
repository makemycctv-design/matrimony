<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable audit trail of every verification decision on a profile
 * (submitted, approved, rejected, re-upload requested, suspended, photo/document
 * moderation). Complements audit_logs with a profile-centric, member-visible
 * history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_verifications', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('member_profile_id')->constrained('member_profiles')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            // profile | identity | photo | document
            $table->string('scope', 20)->default('profile');
            // submitted | approved | rejected | resubmitted | reupload_requested | suspended
            $table->string('action', 30);
            $table->text('reason')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['member_profile_id', 'created_at']);
            $table->index(['scope', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_verifications');
    }
};
