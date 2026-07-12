<?php

namespace App\Notifications;

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
        $source = $this->deposit->source === 'PORTALE' ? 'sito web' : 'sportello';

        return (new MailMessage)
            ->subject('Nuova prenotazione deposito bagagli '.$this->deposit->code)
            ->line("Nuova prenotazione da {$source}.")
            ->line('Cliente: '.$this->deposit->customer_name)
            ->line('Data: '.$this->deposit->booking_date->format('d/m/Y'))
            ->line('Borse: '.$this->deposit->bag_count)
            ->action('Apri in piattaforma', url('/backend/deposito-bagagli/'.$this->deposit->id));
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
