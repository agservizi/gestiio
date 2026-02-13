<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use function App\getInputTelefono;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // register new LoginResponse
        $this->app->singleton(
            \Laravel\Fortify\Contracts\LoginResponse::class,
            \App\Http\Responses\LoginResponse::class
        );

        $this->app->singleton(
            \Laravel\Fortify\Contracts\TwoFactorLoginResponse::class,
            \App\Http\Responses\TwoFactorLoginResponse::class
        );

        /*
        $this->app->singleton(
            \Laravel\Fortify\Contracts\RegisterResponse::class,
            \App\Http\Responses\RegisterResponse::class
        );
        */


        Fortify::authenticateUsing(function (Request $request) {

            if (is_numeric($request->email)) {
                $telefono = getInputTelefono($request->email);
                $user = User::where('telefono', $telefono)->first();
            } else {
                $user = User::where('email', $request->email)->first();
            }

            if ($user &&
                Hash::check($request->password, $user->password)) {
                session()->put('user_login_id', $user->id);
                return $user;
            }
        });


        Fortify::loginView(function () {
            return view('auth.login', ['registrati' => false]);
        });

        Fortify::registerView(function () {
            $record = new User();
            $record->nazione = 'IT';
            return view('auth.registratiAgente', ['cosa' => old('cosa', 'register'), 'record' => $record]);
        });

        Fortify::requestPasswordResetLinkView(function () {
            return view('auth.forgot-password');
        });

        Fortify::resetPasswordView(function ($request) {
            return view('auth.reset-password', ['request' => $request]);
        });

        Fortify::verifyEmailView(function () {
            return view('auth.verify-email');
        });

        //Autenticazione a due fattori
        Fortify::confirmPasswordView(function () {
            return view('auth.password-confirm');
        });
        Fortify::twoFactorChallengeView(function () {
            //return view('auth.sms-challenge');
            return view('auth.two-factor-challenge');
        });


        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $email = (string)$request->email;

            return Limit::perMinute(5)->by($email . $request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
