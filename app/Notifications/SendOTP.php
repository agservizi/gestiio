<?php

namespace App\Notifications;

use App\Actions\TwoFactor\GenerateOTP;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;

class SendOTP extends Notification
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
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [TwilioChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('Il codice di sicurezza per gestiio è ' . $this->getTwoFactorCode($notifiable))
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    public function toTwilio($notifiable)
    {
        Log::info('Invio Sms a ' . $notifiable->nominativo());
        return (new TwilioSmsMessage())
            ->content('Il codice di sicurezza per Gestiio è ' . $this->getTwoFactorCode($notifiable));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
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
