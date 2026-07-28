<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * KYC / identity documents. Files are stored on a private disk and only ever
 * exposed via short-lived signed URLs to authorized staff or the owner. The
 * raw document number is stored encrypted and only the last digits are shown.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_documents', function (Blueprint $table) {
            $table->id();
            $table->ulid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('member_profile_id')->constrained('member_profiles')->cascadeOnDelete();

            // aadhaar | pan | passport | driving_license | voter_id | education_certificate | income_proof | other
            $table->string('type', 40);
            $table->text('document_number')->nullable(); // encrypted at rest
            $table->string('document_last4', 8)->nullable(); // masked hint for staff/UI

            $table->string('disk')->default('private');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('size')->default(0);

            // pending | approved | rejected
            $table->string('status', 12)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['member_profile_id', 'type']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_documents');
    }
};
