<?php

namespace App\Listeners;

use App\Notifications\SendOTP;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Throwable;

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
     */
    public function handle(
        TwoFactorAuthenticationChallenged|TwoFactorAuthenticationEnabled $event
    ): void {
        try {
            app(SendOTP::class)->sendToUser($event->user);
        } catch (Throwable $exception) {
            Log::warning('Invio OTP automatico fallito', [
                'user_id' => $event->user->id,
                'email' => $event->user->email,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
