<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A member's partner criteria, used to drive the explainable match algorithm
 * and to seed search defaults. Multi-value criteria are stored as JSON id
 * arrays. Per-member weight overrides let a member tune the importance of each
 * factor within admin-configured global weights.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('member_profile_id')->constrained('member_profiles')->cascadeOnDelete();

            $table->string('preferred_gender', 10)->nullable();
            $table->unsignedTinyInteger('age_min')->nullable();
            $table->unsignedTinyInteger('age_max')->nullable();
            $table->unsignedSmallInteger('height_min_cm')->nullable();
            $table->unsignedSmallInteger('height_max_cm')->nullable();

            $table->json('marital_statuses')->nullable();
            $table->json('religion_ids')->nullable();
            $table->json('caste_ids')->nullable();
            $table->json('mother_tongue_ids')->nullable();
            $table->json('education_ids')->nullable();
            $table->json('profession_ids')->nullable();
            $table->json('country_ids')->nullable();
            $table->json('state_ids')->nullable();
            $table->json('district_ids')->nullable();
            $table->json('city_ids')->nullable();
            $table->json('diets')->nullable();
            $table->json('employment_types')->nullable();

            $table->unsignedBigInteger('income_min')->nullable();
            $table->unsignedBigInteger('income_max')->nullable();

            $table->boolean('accept_physically_challenged')->default(true);
            $table->boolean('horoscope_match_required')->default(false);
            $table->boolean('only_verified')->default(false);
            $table->boolean('only_with_photo')->default(false);

            // Per-member importance overrides, e.g. {"age": 20, "religion": 5}.
            $table->json('weight_overrides')->nullable();

            $table->timestamps();

            $table->unique('member_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_preferences');
    }
};
