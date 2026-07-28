<?php

namespace App\Providers;

use App\Events\Profile\ProfilePhotoModerated;
use App\Events\Profile\ProfileRejected;
use App\Events\Profile\ProfileVerified;
use App\Listeners\RecordSuccessfulLogin;
use App\Listeners\SendPhotoModeratedNotification;
use App\Listeners\SendProfileRejectedNotification;
use App\Listeners\SendProfileVerifiedNotification;
use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsSender;
use App\Support\Tenancy\TenantManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One tenant context per request lifecycle.
        $this->app->singleton(TenantManager::class);

        // Resolve the configured SMS driver. Defaults to the safe log driver.
        $this->app->bind(SmsSender::class, function () {
            return match (config('sms.default', 'log')) {
                // Additional provider drivers are wired up in later phases.
                default => new LogSmsSender,
            };
        });
    }

    public function boot(): void
    {
        // Eager-load Inertia/React entrypoint assets.
        Vite::prefetch(concurrency: 3);

        // Strong, consistent password policy across web + API.
        Password::defaults(function () {
            $rule = Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();

            return $this->app->isProduction() ? $rule : Password::min(8)->letters()->numbers();
        });

        // Catch lazy-loading and mass-assignment mistakes early outside
        // production. We deliberately do NOT enable preventAccessingMissingAttributes
        // because partially-hydrated model instances (e.g. freshly built factory
        // instances) are legitimate and should return null, not throw.
        $strict = ! $this->app->isProduction();
        Model::preventLazyLoading($strict);
        Model::preventSilentlyDiscardingAttributes($strict);
        Model::unguard(false);

        // Record device/login history on every successful authentication.
        Event::listen(Login::class, RecordSuccessfulLogin::class);

        // Verification & moderation notifications.
        Event::listen(ProfileVerified::class, SendProfileVerifiedNotification::class);
        Event::listen(ProfileRejected::class, SendProfileRejectedNotification::class);
        Event::listen(ProfilePhotoModerated::class, SendPhotoModeratedNotification::class);
    }
}
