<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payment attempts and their lifecycle. We persist Razorpay identifiers, the
 * server-side signature verification result, amounts (paise), method and
 * status. An idempotency_key guards against duplicate order creation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->nullable()->constrained('subscription_plans')->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();

            $table->string('razorpay_order_id')->nullable()->index();
            $table->string('razorpay_payment_id')->nullable()->index();
            $table->text('razorpay_signature')->nullable();
            $table->boolean('signature_verified')->default(false);

            $table->unsignedBigInteger('subtotal_paise')->default(0);
            $table->unsignedBigInteger('discount_paise')->default(0);
            $table->unsignedBigInteger('tax_paise')->default(0);
            $table->unsignedBigInteger('amount_paise')->default(0); // total charged
            $table->unsignedBigInteger('refunded_paise')->default(0);
            $table->string('currency', 3)->default('INR');
            $table->string('method', 30)->nullable(); // upi | card | netbanking | wallet ...

            // created | pending | authorized | captured | failed | refunded | partially_refunded | cancelled
            $table->string('status', 20)->default('created');
            $table->string('failure_reason')->nullable();
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
