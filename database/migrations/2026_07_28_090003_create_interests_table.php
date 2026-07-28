<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expressions of interest between members. Contact details stay hidden until a
 * mutual accept. One active interest per (sender, receiver) pair.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interests', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('sender_profile_id')->constrained('member_profiles')->cascadeOnDelete();
            $table->foreignId('receiver_profile_id')->constrained('member_profiles')->cascadeOnDelete();

            // sent | accepted | declined | withdrawn
            $table->string('status', 12)->default('sent');
            $table->string('message', 500)->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['sender_profile_id', 'receiver_profile_id']);
            $table->index(['receiver_profile_id', 'status']);
            $table->index(['sender_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interests');
    }
};
