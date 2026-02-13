<?php

namespace App\Listeners;

use App\Notifications\SendOTP;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;

class SendTwoFactorCodeListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(
        TwoFactorAuthenticationChallenged|TwoFactorAuthenticationEnabled $event
    ): void {
        $event->user->notify(app(SendOTP::class));
    }
}
