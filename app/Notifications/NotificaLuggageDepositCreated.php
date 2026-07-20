<?php

namespace App\Notifications;

use App\Http\Support\LuggageBilingualMail;
use App\Models\LuggageDeposit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaLuggageDepositCreated extends Notification
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
        $isPortal = $this->deposit->source === 'PORTALE';
        $sourceEn = $isPortal ? 'website (agenziaplinio.it)' : 'front desk';
        $sourceIt = $isPortal ? 'sito web (agenziaplinio.it)' : 'sportello';
        $checkIn = $this->deposit->expected_check_in?->format('d/m/Y H:i') ?? '—';
        $checkOut = $this->deposit->expected_check_out?->format('d/m/Y H:i') ?? '—';

        return (new MailMessage)
            ->subject(LuggageBilingualMail::subject(
                'Staff — luggage storage booking confirmation',
                'Staff — conferma prenotazione deposito bagagli',
                $this->deposit->code
            ))
            ->line(LuggageBilingualMail::line(
                'A new luggage storage booking has been registered from the '.$sourceEn.'.',
                'Nuova prenotazione deposito bagagli registrata dal '.$sourceIt.'.'
            ))
            ->line(LuggageBilingualMail::line(
                'Booking code: '.$this->deposit->code,
                'Codice prenotazione: '.$this->deposit->code
            ))
            ->line(LuggageBilingualMail::line(
                'Customer: '.$this->deposit->customer_name,
                'Cliente: '.$this->deposit->customer_name
            ))
            ->line(LuggageBilingualMail::line(
                'Email: '.($this->deposit->customer_email ?: '—'),
                'Email: '.($this->deposit->customer_email ?: '—')
            ))
            ->line(LuggageBilingualMail::line(
                'Phone: '.($this->deposit->customer_phone ?: '—'),
                'Telefono: '.($this->deposit->customer_phone ?: '—')
            ))
            ->line(LuggageBilingualMail::line(
                'Bags: '.$this->deposit->bag_count,
                'Borse: '.$this->deposit->bag_count
            ))
            ->line(LuggageBilingualMail::line(
                'Booking date: '.$this->deposit->booking_date->format('d/m/Y'),
                'Data prenotazione: '.$this->deposit->booking_date->format('d/m/Y')
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
                LuggageBilingualMail::actionLabel('Open booking in Gestiio', 'Apri prenotazione in Gestiio'),
                url('/backend/deposito-bagagli/'.$this->deposit->id)
            );
    }

    public function toArray($notifiable): array
    {
        return [
            'tipo' => 'luggage_deposit_created',
            'deposit_id' => $this->deposit->id,
            'code' => $this->deposit->code,
            'customer_name' => $this->deposit->customer_name,
            'source' => $this->deposit->source,
        ];
    }
}
