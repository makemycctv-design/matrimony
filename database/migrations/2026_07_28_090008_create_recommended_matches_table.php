<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Materialized recommendation cache refreshed by a scheduled job. Stores the
 * computed compatibility score and the explainable reasons for each candidate
 * so the member dashboard renders instantly at scale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recommended_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('member_profile_id')->constrained('member_profiles')->cascadeOnDelete();
            $table->foreignId('matched_profile_id')->constrained('member_profiles')->cascadeOnDelete();

            $table->unsignedTinyInteger('score');
            $table->json('reasons')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->unique(['member_profile_id', 'matched_profile_id'], 'recommendation_unique');
            $table->index(['member_profile_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommended_matches');
    }
};
