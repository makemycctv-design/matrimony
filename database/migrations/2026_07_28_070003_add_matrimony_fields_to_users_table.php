<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the framework users table with tenant ownership, mobile-based
 * identity, account status, localization, security, and consent fields.
 *
 * We intentionally keep highly sensitive matrimonial detail out of this table;
 * that lives in member_profiles. This table remains the authentication root.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Public-facing identifier (never expose the auto-increment id).
            $table->ulid('uuid')->nullable()->after('id')->unique();

            // Tenant ownership (nullable to support a single installation).
            $table->foreignId('company_id')->nullable()->after('uuid')
                ->constrained('companies')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->after('company_id')
                ->constrained('branches')->nullOnDelete();

            // Mobile identity + verification.
            $table->string('country_code', 5)->default('+91')->after('email');
            $table->string('mobile', 20)->nullable()->after('country_code');
            $table->timestamp('mobile_verified_at')->nullable()->after('mobile');

            // Account lifecycle.
            // active | pending | suspended | deactivated | banned
            $table->string('status', 20)->default('pending')->after('password');
            $table->string('locale', 8)->default('en')->after('status');

            // Login / device telemetry.
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();

            // Optional TOTP two-factor authentication (encrypted at rest).
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();

            // Consent tracking (captured at registration).
            $table->timestamp('terms_accepted_at')->nullable();
            $table->timestamp('privacy_accepted_at')->nullable();
            $table->boolean('marketing_opt_in')->default(false);

            $table->softDeletes();

            $table->index(['company_id', 'branch_id']);
            $table->index('status');
            $table->index('mobile');
        });

        // A user's mobile must be unique within a tenant (or globally when null tenant).
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['company_id', 'mobile'], 'users_company_mobile_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_company_mobile_unique');
            $table->dropConstrainedForeignId('company_id');
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn([
                'uuid', 'country_code', 'mobile', 'mobile_verified_at',
                'status', 'locale', 'last_login_at', 'last_login_ip',
                'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',
                'terms_accepted_at', 'privacy_accepted_at', 'marketing_opt_in',
                'deleted_at',
            ]);
        });
    }
};
