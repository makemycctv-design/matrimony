<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Self-referencing geographic hierarchy: country > state > district > city.
 * Used by profiles, search filters, and location-based matching.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->nullOnDelete();

            // country | state | district | city
            $table->string('type', 20);
            $table->string('name');
            $table->string('slug');
            $table->json('name_translations')->nullable();
            $table->string('code', 20)->nullable(); // ISO / state code
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
            $table->index(['parent_id', 'type']);
            $table->index('name');
            $table->unique(['parent_id', 'type', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
