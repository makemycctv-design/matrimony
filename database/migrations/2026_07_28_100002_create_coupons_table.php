<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Discount coupons and referral codes. Supports percentage/fixed value,
 * validity window, usage caps (global + per user), minimum amount and plan
 * restrictions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();

            $table->string('code');
            $table->string('type', 10)->default('percent'); // percent | fixed
            $table->unsignedBigInteger('value'); // percent (0-100) or paise
            $table->unsignedBigInteger('max_discount_paise')->nullable(); // cap for percent coupons
            $table->unsignedBigInteger('min_amount_paise')->default(0);

            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('per_user_limit')->default(1);
            $table->unsignedInteger('redeemed_count')->default(0);

            $table->json('applicable_plan_ids')->nullable(); // null = all plans
            $table->boolean('is_referral')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
