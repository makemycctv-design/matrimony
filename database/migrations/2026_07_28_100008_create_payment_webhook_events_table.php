<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Durable record of every inbound provider webhook. The provider event id is
 * unique, giving us replay/idempotency protection: a re-delivered event is
 * recognised and never processed twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique(); // provider's x-razorpay-event-id
            $table->string('event_type')->nullable();
            $table->boolean('signature_verified')->default(false);
            $table->json('payload')->nullable();
            $table->string('status', 20)->default('received'); // received | processed | ignored | failed
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
    }
};
