<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Key/value platform settings, optionally tenant scoped. Grouped for admin UI
 * (branding, seo, mail, sms, whatsapp, razorpay, matching, general).
 * Secret values are flagged so they can be masked in the UI/logs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('group', 50)->default('general');
            $table->string('key');
            $table->longText('value')->nullable();
            $table->string('type', 20)->default('string'); // string | integer | boolean | json | secret
            $table->boolean('is_secret')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'group', 'key']);
            $table->index('group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
