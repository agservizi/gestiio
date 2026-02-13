<?php

namespace App\Providers;

use App\Listeners\EmailLogger;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\RecordFailedLoginAttempt;
use App\Listeners\SendTwoFactorCodeListener;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Event;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        Login::class=>[
            LogSuccessfulLogin::class
        ],
        Failed::class=>[
            RecordFailedLoginAttempt::class
        ],
        MessageSent::class=>[
            EmailLogger::class
        ],
        /*
        TwoFactorAuthenticationChallenged::class => [
            SendTwoFactorCodeListener::class,
        ],
        TwoFactorAuthenticationEnabled::class => [
            SendTwoFactorCodeListener::class,
        ],
        */

    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
