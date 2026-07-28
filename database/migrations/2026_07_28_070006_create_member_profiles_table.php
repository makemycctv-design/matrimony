<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The core matrimonial profile. One-to-one with a user.
 *
 * Sensitive fields are kept here (separate from auth) so visibility and
 * privacy rules can be enforced at the profile layer. Income is stored as a
 * range; money-like values never use floats. Caste/horoscope/physical status
 * are optional and respectful.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_profiles', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Human-friendly matrimony id, e.g. MTR000123.
            $table->string('profile_code')->nullable()->unique();

            // Who created/manages this profile (self, parent, sibling, relative, friend).
            $table->string('profile_managed_by', 20)->default('self');
            $table->foreignId('created_for_user_id')->nullable()->constrained('users')->nullOnDelete();

            // --- Basic information ---
            $table->string('first_name');
            $table->string('last_name')->nullable();
            // full | first_only | initials | hidden
            $table->string('name_display', 20)->default('first_only');
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 10)->nullable(); // male | female | other
            // never_married | divorced | widowed | separated | annulled
            $table->string('marital_status', 20)->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->unsignedSmallInteger('weight_kg')->nullable();
            // Respectful, optional: none | physically_challenged
            $table->string('physical_status', 30)->default('none');
            $table->string('body_type', 20)->nullable();
            $table->string('complexion', 20)->nullable();

            // --- Community (all optional / member controlled) ---
            $table->foreignId('religion_id')->nullable()->constrained('religions')->nullOnDelete();
            $table->foreignId('caste_id')->nullable()->constrained('castes')->nullOnDelete();
            $table->foreignId('sub_caste_id')->nullable()->constrained('sub_castes')->nullOnDelete();
            $table->foreignId('mother_tongue_id')->nullable()->constrained('mother_tongues')->nullOnDelete();
            $table->string('gothra')->nullable();

            // --- Education & profession ---
            $table->foreignId('education_id')->nullable()->constrained('educations')->nullOnDelete();
            $table->string('education_detail')->nullable();
            $table->foreignId('profession_id')->nullable()->constrained('professions')->nullOnDelete();
            $table->string('profession_detail')->nullable();
            $table->string('company_name')->nullable();
            $table->string('employment_type', 30)->nullable(); // government | private | business | self_employed | not_working
            // Income stored as an inclusive integer range in INR (annual). Nullable/optional.
            $table->unsignedBigInteger('annual_income_min')->nullable();
            $table->unsignedBigInteger('annual_income_max')->nullable();

            // --- Location ---
            $table->foreignId('country_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('native_place')->nullable();
            $table->string('residency_status', 30)->nullable();

            // --- Family ---
            $table->string('family_type', 20)->nullable(); // nuclear | joint
            $table->string('family_status', 20)->nullable(); // middle_class | upper_middle | affluent | rich
            $table->string('family_values', 20)->nullable(); // traditional | moderate | liberal
            $table->string('father_occupation')->nullable();
            $table->string('mother_occupation')->nullable();
            $table->unsignedTinyInteger('brothers')->nullable();
            $table->unsignedTinyInteger('brothers_married')->nullable();
            $table->unsignedTinyInteger('sisters')->nullable();
            $table->unsignedTinyInteger('sisters_married')->nullable();
            $table->text('family_details')->nullable();

            // --- Lifestyle ---
            $table->string('diet', 20)->nullable(); // vegetarian | non_vegetarian | eggetarian | vegan
            $table->string('smoking', 10)->nullable(); // no | occasionally | yes
            $table->string('drinking', 10)->nullable();
            $table->json('lifestyle')->nullable(); // hobbies, interests, languages known, etc.

            // --- Horoscope (optional) ---
            $table->boolean('horoscope_enabled')->default(false);
            $table->string('birth_time')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('star')->nullable();      // nakshatra
            $table->string('rasi')->nullable();       // moon sign
            $table->string('dosham', 20)->nullable(); // none | manglik | partial | dont_know
            $table->string('horoscope_document_path')->nullable();

            // --- Free text ---
            $table->text('about_me')->nullable();
            $table->text('partner_expectations_note')->nullable();

            // --- Status / verification lifecycle ---
            // draft | submitted | under_review | verified | rejected | suspended | deactivated
            $table->string('status', 20)->default('draft');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_photo_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // --- Completeness ---
            $table->unsignedTinyInteger('completion_percentage')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes tuned for search & matching.
            $table->index(['company_id', 'status']);
            $table->index(['gender', 'status', 'is_verified']);
            $table->index(['religion_id', 'caste_id']);
            $table->index('mother_tongue_id');
            $table->index(['state_id', 'district_id', 'city_id']);
            $table->index('date_of_birth');
            $table->index('height_cm');
            $table->index('last_active_at');
            $table->index(['is_featured', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_profiles');
    }
};
