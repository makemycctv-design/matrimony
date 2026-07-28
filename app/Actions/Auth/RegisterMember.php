<?php

namespace App\Actions\Auth;

use App\Enums\UserStatus;
use App\Models\MemberProfile;
use App\Models\ProfilePreference;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Support\Tenancy\TenantManager;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Creates a new member: user account + matrimonial profile + default privacy
 * preferences + consent records, assigns the Registered Member role, fires the
 * email verification event, and issues a mobile OTP. Runs in a transaction so a
 * partial signup can never persist.
 */
class RegisterMember
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly TenantManager $tenants,
    ) {}

    /**
     * @param  array{name:string,email:string,country_code?:string,mobile:string,password:string,gender?:string,profile_for?:string,accept_terms:bool,accept_privacy:bool,marketing_opt_in?:bool,locale?:string}  $data
     * @param  array{ip?:string,user_agent?:string}  $context
     */
    public function handle(array $data, array $context = []): User
    {
        $now = Carbon::now();

        $user = DB::transaction(function () use ($data, $context, $now): User {
            $user = User::create([
                'company_id' => $this->tenants->id(),
                'name' => $data['name'],
                'email' => $data['email'],
                'country_code' => $data['country_code'] ?? '+91',
                'mobile' => $data['mobile'],
                'password' => Hash::make($data['password']),
                'status' => UserStatus::Pending,
                'locale' => $data['locale'] ?? app()->getLocale(),
                'terms_accepted_at' => $now,
                'privacy_accepted_at' => $now,
                'marketing_opt_in' => (bool) ($data['marketing_opt_in'] ?? false),
            ]);

            $user->assignRole('Registered Member');

            $this->recordConsents($user, $data, $context, $now);

            [$first, $last] = $this->splitName($data['name']);

            // company_id is guarded (tenant integrity); set it explicitly.
            $profile = new MemberProfile([
                'user_id' => $user->id,
                'first_name' => $first,
                'last_name' => $last,
                'gender' => $data['gender'] ?? null,
                'profile_managed_by' => $data['profile_for'] ?? 'self',
            ]);
            $profile->company_id = $user->company_id;
            $profile->save();

            ProfilePreference::create(['member_profile_id' => $profile->id]);

            return $user;
        });

        // Verification side effects run after the transaction commits.
        event(new Registered($user));

        $this->otp->issue('mobile', $user->fullMobile(), 'verification', $user);

        return $user;
    }

    private function recordConsents(User $user, array $data, array $context, Carbon $now): void
    {
        $base = [
            'ip_address' => $context['ip'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'granted_at' => $now,
        ];

        $user->consents()->createMany([
            ['type' => 'terms', 'version' => config('app.legal_version', '1.0'), 'granted' => true, ...$base],
            ['type' => 'privacy', 'version' => config('app.legal_version', '1.0'), 'granted' => true, ...$base],
            ['type' => 'marketing', 'granted' => (bool) ($data['marketing_opt_in'] ?? false), ...$base],
        ]);
    }

    /**
     * @return array{0:string,1:?string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name), 2) ?: [$name];

        return [$parts[0], $parts[1] ?? null];
    }
}
