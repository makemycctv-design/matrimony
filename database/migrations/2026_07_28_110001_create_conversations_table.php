<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A private conversation between two connected members (only created once an
 * interest has been mutually accepted). Participants are stored as an ordered
 * pair so a unique constraint prevents duplicate threads.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('member_one_id')->constrained('member_profiles')->cascadeOnDelete();
            $table->foreignId('member_two_id')->constrained('member_profiles')->cascadeOnDelete();

            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('member_one_read_at')->nullable();
            $table->timestamp('member_two_read_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique(['member_one_id', 'member_two_id'], 'conversation_pair_unique');
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
