<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Otp\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_can_be_verified_with_a_valid_otp(): void
    {
        $user = User::factory()->unverified()->create(['mobile' => '9876500000']);

        // Issue a real OTP (returns the plaintext code for the test).
        $code = app(OtpService::class)->issue('mobile', $user->fullMobile(), 'verification', $user);

        $this->actingAs($user)
            ->post(route('verification.mobile.verify'), ['code' => $code])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertNotNull($user->fresh()->mobile_verified_at);
    }

    public function test_invalid_otp_is_rejected(): void
    {
        $user = User::factory()->unverified()->create(['mobile' => '9876500001']);
        app(OtpService::class)->issue('mobile', $user->fullMobile(), 'verification', $user);

        $this->actingAs($user)
            ->post(route('verification.mobile.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->mobile_verified_at);
    }

    public function test_otp_is_stored_hashed_not_plaintext(): void
    {
        $user = User::factory()->create(['mobile' => '9876500002']);
        $code = app(OtpService::class)->issue('mobile', $user->fullMobile(), 'verification', $user);

        $this->assertDatabaseMissing('otp_verifications', ['code_hash' => $code]);
        $this->assertDatabaseHas('otp_verifications', ['destination' => $user->fullMobile(), 'channel' => 'mobile']);
    }
}
