<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Branches allow a single company/tenant to isolate staff work queues and
 * members by physical or virtual office. Optional for a single installation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();

            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();

            $table->string('name');
            $table->string('code')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
