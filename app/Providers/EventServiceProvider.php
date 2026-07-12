<?php

namespace App\Providers;

use App\Events\LuggageDepositCheckedOut;
use App\Events\LuggageDepositCreated;
use App\Listeners\EmailLogger;
use App\Listeners\LogLuggageDepositCheckedIn;
use App\Listeners\NotifyStaffOnLuggageDepositCreated;
use App\Listeners\SendLuggagePickupQrEmail;
use App\Listeners\SendLuggageDepositReceiptEmail;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\RecordFailedLoginAttempt;
use App\Listeners\RespectUserNotificationPreferences;
use App\Listeners\SendTwoFactorCodeListener;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationSending;
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
        Login::class => [
            LogSuccessfulLogin::class,
        ],
        Failed::class => [
            RecordFailedLoginAttempt::class,
        ],
        MessageSent::class => [
            EmailLogger::class,
        ],
        NotificationSending::class => [
            RespectUserNotificationPreferences::class,
        ],
        LuggageDepositCreated::class => [
            NotifyStaffOnLuggageDepositCreated::class,
        ],
        \App\Events\LuggageDepositCheckedIn::class => [
            LogLuggageDepositCheckedIn::class,
            SendLuggagePickupQrEmail::class,
        ],
        LuggageDepositCheckedOut::class => [
            SendLuggageDepositReceiptEmail::class,
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
