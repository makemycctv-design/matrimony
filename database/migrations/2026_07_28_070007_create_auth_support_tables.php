<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Authentication & security support tables:
 *  - login_histories: device/login audit for members and staff
 *  - user_consents: immutable log of consent given (terms/privacy/marketing)
 *  - otp_verifications: short-lived hashed OTP codes for email/mobile
 *  - account_deletion_requests: GDPR/DPDP-style deletion workflow
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device')->nullable();
            $table->string('platform')->nullable();
            $table->string('browser')->nullable();
            $table->string('location')->nullable();
            $table->string('status', 20)->default('success'); // success | failed
            $table->timestamp('logged_in_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 30); // terms | privacy | marketing | cookies | data_processing
            $table->string('version', 20)->nullable();
            $table->boolean('granted')->default(true);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('granted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });

        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('channel', 10); // email | mobile
            $table->string('destination'); // email address or phone number
            $table->string('purpose', 30)->default('verification'); // verification | login | password_reset
            $table->string('code_hash'); // hashed OTP, never stored in plaintext
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['destination', 'channel', 'purpose']);
            $table->index('expires_at');
        });

        Schema::create('account_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason')->nullable();
            // pending | approved | rejected | cancelled | completed
            $table->string('status', 20)->default('pending');
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('scheduled_for')->nullable(); // grace period before purge
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
        Schema::dropIfExists('otp_verifications');
        Schema::dropIfExists('user_consents');
        Schema::dropIfExists('login_histories');
    }
};
