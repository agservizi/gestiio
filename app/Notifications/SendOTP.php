<?php

namespace App\Notifications;

use App\Actions\TwoFactor\GenerateOTP;
use App\Models\User;
use App\Notifications\OtpCodeNotification;
use Illuminate\Bus\Queueable;
use RuntimeException;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;

class SendOTP
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     */
    public function sendToUser(User $user): void
    {
        if (!$user->email) {
            throw new RuntimeException('Email utente non disponibile.');
        }

        $otp = $this->getTwoFactorCode($user);
        if (!$otp) {
            throw new RuntimeException('OTP non generabile: autenticazione a due fattori non configurata.');
        }

        $user->notify(new OtpCodeNotification($otp));
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     */
    public function getTwoFactorCode(User $notifiable): ?string
    {
        if (!$notifiable->two_factor_secret) {
            return null;
        }

        return GenerateOTP::for(
            decrypt($notifiable->two_factor_secret)
        );
    }

}
