<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shortlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('member_profile_id')->constrained('member_profiles')->cascadeOnDelete();
            $table->foreignId('shortlisted_profile_id')->constrained('member_profiles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['member_profile_id', 'shortlisted_profile_id'], 'shortlist_unique');
            $table->index('member_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shortlists');
    }
};
