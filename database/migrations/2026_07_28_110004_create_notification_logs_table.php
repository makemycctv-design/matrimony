<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Delivery log for outbound notifications across channels (mail/sms/whatsapp/
 * push). Complements the in-app `notifications` table with per-channel status,
 * enabling retries and delivery audits without exposing sensitive content.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('channel', 20); // mail | sms | whatsapp | database | push
            $table->string('type');        // notification class / event key
            $table->string('destination')->nullable(); // masked recipient
            $table->string('status', 20)->default('queued'); // queued | sent | failed
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'channel']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
