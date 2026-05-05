<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\UserEmployee;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use Illuminate\Validation\ValidationException;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
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

        // Fortify::authenticateUsing(function (Request $request) {
        //     $user = UserEmployee::with('employee')
        //         ->where('email', $request->email)
        //         // ->orWhere('username', $request->email)
        //         ->first();

        //     if ($user && Hash::check($request->password, $user->password)) {
        //         return $user;
        //     }
        // });

        Fortify::authenticateUsing(function (Request $request) {

            $key = Str::lower($request->email) . '|' . $request->ip();

            // 🔒 Si ya está bloqueado
            if (RateLimiter::tooManyAttempts($key, 3)) {

                $seconds = RateLimiter::availableIn($key);

                throw ValidationException::withMessages([
                    'email' => "Bloqueado. Intenta en {$seconds} segundos o contacta a RH.",
                ]);
            }

            $user = UserEmployee::with('employee')
                ->where('email', $request->email)
                ->first();

            // ✅ Login correcto
            if ($user && Hash::check($request->password, $user->password)) {
                RateLimiter::clear($key);
                return $user;
            }

            // ❌ Login incorrecto → suma intento
            RateLimiter::hit($key, 60); // 5 minutos

            throw ValidationException::withMessages([
                'email' => 'Credenciales incorrectas.',
            ]);
        });

        // RateLimiter::for('login', function (Request $request) {
        //     $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

        //     return Limit::perMinute(1)->by($throttleKey);
        // });

        RateLimiter::for('login', function (Request $request) {
            return Limit::none();
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
