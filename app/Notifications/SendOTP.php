<?php

namespace App\Notifications;

use App\Actions\TwoFactor\GenerateOTP;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
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

        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');
        $from = $fromName ? sprintf('%s <%s>', $fromName, $fromAddress) : $fromAddress;

        $apiKey = config('services.resend.key');
        if (!$apiKey) {
            throw new RuntimeException('RESEND_KEY non configurata.');
        }

        $response = Http::timeout(15)
            ->withToken($apiKey)
            ->acceptJson()
            ->post('https://api.resend.com/emails', [
                'from' => $from,
                'to' => [$user->email],
                'subject' => 'Codice OTP di accesso - Gestiio',
                'text' => "Ciao {$user->nominativo()}\n\nIl tuo codice OTP per l'accesso è: {$otp}\n\nSe non hai richiesto questo accesso, ignora questa email.",
            ]);

        if ($response->failed()) {
            $body = $response->json();
            $message = is_array($body) && isset($body['message']) ? $body['message'] : $response->body();
            throw new RuntimeException('Resend API error: ' . $message);
        }
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
