<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Subscription plans (Free/Basic/Premium/VIP/Custom). Money is stored as
 * integer paise — never floats. Feature access and numeric limits live in JSON
 * so plans stay flexible without schema churn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->string('tier', 20)->default('basic'); // free | basic | premium | vip | custom
            $table->text('description')->nullable();

            $table->unsignedBigInteger('price_paise')->default(0);
            $table->decimal('gst_percent', 5, 2)->default(18);
            $table->string('currency', 3)->default('INR');
            $table->unsignedInteger('duration_days')->default(30);
            $table->unsignedInteger('trial_days')->default(0);

            // Feature flags.
            $table->boolean('contact_view_access')->default(false);
            $table->boolean('messaging_access')->default(false);
            $table->boolean('advanced_search')->default(false);
            $table->boolean('profile_boost')->default(false);
            $table->boolean('profile_highlight')->default(false);
            $table->boolean('verification_priority')->default(false);

            // Numeric limits (null = unlimited).
            $table->unsignedInteger('max_profile_views_per_day')->nullable();
            $table->unsignedInteger('max_interests_per_day')->nullable();
            $table->unsignedInteger('max_contact_views')->nullable();

            $table->json('features')->nullable(); // marketing bullet list
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'slug']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
