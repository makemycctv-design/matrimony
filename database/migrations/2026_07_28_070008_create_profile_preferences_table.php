<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-member privacy, visibility, and notification preferences.
 * One-to-one with member_profiles. Enforced server-side by policies/scopes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_profile_id')->constrained('member_profiles')->cascadeOnDelete();

            // Visibility audience values: everyone | members | verified | premium | connected | none
            $table->string('photo_visibility', 20)->default('members');
            $table->string('contact_visibility', 20)->default('connected');
            $table->string('horoscope_visibility', 20)->default('connected');
            $table->string('income_visibility', 20)->default('members');

            // Who may send this member an interest.
            $table->string('interest_from', 20)->default('members'); // everyone | members | verified | premium

            // Discovery controls.
            $table->boolean('appear_in_search')->default(true);
            $table->boolean('visible_to_verified_only')->default(false);
            $table->boolean('visible_to_premium_only')->default(false);
            $table->boolean('hide_details_until_interest_accepted')->default(true);
            $table->boolean('show_online_status')->default(true);
            $table->boolean('show_last_seen')->default(true);

            // Notification channel preferences.
            $table->boolean('notify_email')->default(true);
            $table->boolean('notify_sms')->default(false);
            $table->boolean('notify_whatsapp')->default(false);
            $table->boolean('notify_in_app')->default(true);
            $table->boolean('notify_push')->default(false);

            $table->timestamps();

            $table->unique('member_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_preferences');
    }
};
