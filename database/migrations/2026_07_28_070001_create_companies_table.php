<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Companies represent the tenant / brand root of the platform.
 *
 * A single matrimony business runs as one company. The schema is prepared for
 * future multi-tenant SaaS usage where every tenant-owned entity carries a
 * company_id for isolation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('legal_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            // Branding
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('primary_color', 9)->default('#be123c');
            $table->string('secondary_color', 9)->default('#0f766e');

            // Localization defaults
            $table->string('default_locale', 8)->default('en');
            $table->json('supported_locales')->nullable();
            $table->string('timezone', 64)->default('Asia/Kolkata');
            $table->string('currency', 3)->default('INR');

            // Tax / legal
            $table->string('gstin', 20)->nullable();
            $table->string('pan', 20)->nullable();
            $table->json('address')->nullable();

            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
