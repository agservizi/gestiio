<?php

namespace App\Notifications;

use App\Http\Support\LuggageBilingualMail;
use App\Models\LuggageDeposit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaLuggageBookingConfirmation extends Notification
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
        $checkIn = $this->deposit->expected_check_in?->format('d/m/Y H:i') ?? '—';
        $checkOut = $this->deposit->expected_check_out?->format('d/m/Y H:i') ?? '—';

        return (new MailMessage)
            ->subject(LuggageBilingualMail::subject(
                'Luggage storage booking confirmation',
                'Conferma prenotazione deposito bagagli',
                $this->deposit->code
            ))
            ->greeting(LuggageBilingualMail::greeting(
                $this->deposit->customer_name,
                'Hello',
                'Ciao'
            ))
            ->line(LuggageBilingualMail::line(
                'Your luggage storage booking is confirmed. Please keep your booking code for check-in at the desk.',
                'La tua prenotazione deposito bagagli è confermata. Conserva il codice di prenotazione per il check-in in agenzia.'
            ))
            ->line(LuggageBilingualMail::line(
                'Booking code: '.$this->deposit->code,
                'Codice prenotazione: '.$this->deposit->code
            ))
            ->line(LuggageBilingualMail::line(
                'Bags: '.$this->deposit->bag_count,
                'Borse: '.$this->deposit->bag_count
            ))
            ->line(LuggageBilingualMail::line(
                'Expected drop-off: '.$checkIn,
                'Check-in previsto: '.$checkIn
            ))
            ->line(LuggageBilingualMail::line(
                'Expected pickup: '.$checkOut,
                'Ritiro previsto: '.$checkOut
            ))
            ->action(
                LuggageBilingualMail::actionLabel('View booking status', 'Verifica stato prenotazione'),
                $this->deposit->verifyUrl()
            )
            ->line(LuggageBilingualMail::line(
                'You will receive a separate email with the pickup QR code when your scheduled pickup time approaches.',
                'Riceverai un\'altra email con il QR per il ritiro quando si avvicinerà la data di ritiro indicata.'
            ));
    }
}
