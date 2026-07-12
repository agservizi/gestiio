<?php

namespace App\Notifications;

use App\Models\LuggageDeposit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaLuggageDepositReceipt extends Notification
{
    use Queueable;

    public function __construct(
        protected LuggageDeposit $deposit,
        protected float $totalAmount
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Ricevuta deposito bagagli '.$this->deposit->code)
            ->greeting('Grazie '.$this->deposit->customer_name)
            ->line('Il tuo deposito bagagli è stato completato.')
            ->line('Codice: '.$this->deposit->code)
            ->line('Importo totale: €'.number_format($this->totalAmount, 2, ',', '.'))
            ->line('Metodo pagamento: '.($this->deposit->payment_method ?? '—'));
    }
}
