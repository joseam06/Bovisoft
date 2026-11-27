<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend('auth.password',fn($manager,$app)=>new \App\Auth\VerifiedPasswordBrokerManager($app));
        $this->app->bind(\Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse::class,\App\Http\Responses\PrivatePasswordLinkResponse::class);
        $this->app->bind(\Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse::class,\App\Http\Responses\PrivatePasswordLinkResponse::class);
        $this->app->bind(\Laravel\Fortify\Contracts\FailedPasswordResetResponse::class,\App\Http\Responses\PrivatePasswordResetFailure::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);
        Fortify::verifyEmailView(fn()=>view('auth.verify-email'));
        Fortify::authenticateUsing(function(Request $request){
            $user=\App\Models\User::where('email_identity',\App\Models\User::normalizeEmail((string)$request->input('email')))->first();
            if(!$user || !$user->password_login_enabled || !\Illuminate\Support\Facades\Hash::check((string)$request->input('password'),$user->getAuthPassword()))return null;
            if(!$user->password_set_at){$user->password_set_at=now();$user->save();}
            return $user;
        });
        RateLimiter::for('account-requests',fn(Request $request)=>[
            Limit::perMinute(5)->by($request->path().'|'.$request->ip().'|'.hash('sha256',\App\Models\User::normalizeEmail((string)$request->input('email')))),
            Limit::perHour(30)->by($request->path().'|'.$request->ip()),
        ]);
        RateLimiter::for('account-security',fn(Request $request)=>Limit::perMinute(5)->by('security|'.($request->user()?->id??$request->ip()).'|'.$request->route()?->getName()));
        RateLimiter::for('account-verification',fn(Request $request)=>Limit::perMinute(3)->by('verification|'.($request->user()?->id??$request->ip())));

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
