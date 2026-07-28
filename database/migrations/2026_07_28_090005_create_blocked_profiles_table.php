<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('member_profile_id')->constrained('member_profiles')->cascadeOnDelete();
            $table->foreignId('blocked_profile_id')->constrained('member_profiles')->cascadeOnDelete();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->unique(['member_profile_id', 'blocked_profile_id'], 'block_unique');
            $table->index('blocked_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_profiles');
    }
};
