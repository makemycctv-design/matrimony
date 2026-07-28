<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configurable master / lookup data used across profiles, search, and matching.
 *
 * Every table is optionally tenant scoped (company_id nullable) so a SaaS
 * tenant can maintain its own community taxonomy while a single installation
 * shares one global set. Caste/sub-caste are optional and community sensitive;
 * they are only surfaced when a member enables them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('religions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->json('name_translations')->nullable(); // {"ml": "..."}
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index('is_active');
        });

        Schema::create('castes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('religion_id')->nullable()->constrained('religions')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->json('name_translations')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'religion_id', 'slug']);
            $table->index(['religion_id', 'is_active']);
        });

        Schema::create('sub_castes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('caste_id')->constrained('castes')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->json('name_translations')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['caste_id', 'slug']);
            $table->index(['caste_id', 'is_active']);
        });

        Schema::create('mother_tongues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->json('name_translations')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index('is_active');
        });

        Schema::create('educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            // Broad grouping used for matching: school | diploma | graduate | post_graduate | doctorate | professional
            $table->string('category', 40)->nullable();
            $table->json('name_translations')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index(['category', 'is_active']);
        });

        Schema::create('professions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('category', 60)->nullable();
            $table->json('name_translations')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professions');
        Schema::dropIfExists('educations');
        Schema::dropIfExists('mother_tongues');
        Schema::dropIfExists('sub_castes');
        Schema::dropIfExists('castes');
        Schema::dropIfExists('religions');
    }
};
