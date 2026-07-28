<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messages within a conversation. Includes moderation fields so abusive
 * messages can be flagged/hidden and reviewed by staff.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('sender_profile_id')->constrained('member_profiles')->cascadeOnDelete();

            $table->text('body');
            $table->timestamp('read_at')->nullable();

            // Moderation.
            $table->boolean('is_flagged')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->string('flag_reason')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['is_flagged', 'is_hidden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
