<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tax invoices / receipts generated on a successful payment. GST is broken out
 * (CGST+SGST for intra-state, IGST otherwise) and the seller's GSTIN captured.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();

            $table->string('invoice_number')->unique();
            $table->string('billing_name')->nullable();
            $table->json('billing_address')->nullable();
            $table->string('seller_gstin', 20)->nullable();
            $table->string('place_of_supply')->nullable();

            $table->unsignedBigInteger('subtotal_paise');
            $table->unsignedBigInteger('discount_paise')->default(0);
            $table->unsignedBigInteger('tax_paise')->default(0);
            $table->unsignedBigInteger('cgst_paise')->default(0);
            $table->unsignedBigInteger('sgst_paise')->default(0);
            $table->unsignedBigInteger('igst_paise')->default(0);
            $table->unsignedBigInteger('total_paise');
            $table->string('currency', 3)->default('INR');

            $table->timestamp('issued_at');
            $table->timestamps();

            $table->index(['user_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
