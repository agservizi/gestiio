<?php

namespace App\Notifications;

use App\Http\Support\LuggageBilingualMail;
use App\Models\LuggageDeposit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaLuggageThankYou extends Notification
{
    use Queueable;

    public function __construct(protected LuggageDeposit $deposit)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(LuggageBilingualMail::subject(
                'Thank you for using our luggage storage',
                'Grazie per aver utilizzato il deposito bagagli',
                $this->deposit->code
            ))
            ->greeting(LuggageBilingualMail::greeting(
                $this->deposit->customer_name,
                'Thank you',
                'Grazie'
            ))
            ->line(LuggageBilingualMail::line(
                'Thank you for leaving your luggage with us. We hope to welcome you again on your next visit.',
                'Grazie per aver lasciato i tuoi bagagli in deposito. Speriamo di rivederti alla prossima occasione.'
            ))
            ->line(LuggageBilingualMail::line(
                'Booking code: '.$this->deposit->code,
                'Codice prenotazione: '.$this->deposit->code
            ))
            ->line(LuggageBilingualMail::line(
                'We wish you a pleasant journey.',
                'Ti auguriamo un buon viaggio.'
            ));
    }
}
