<?php

namespace App\Notifications;

use App\Http\Support\LuggageQrCode;
use App\Models\LuggageDeposit;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class NotificaLuggagePickupQr extends Notification
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
        $pickupUrl = $this->deposit->pickupUrl();
        $qrSvg = LuggageQrCode::svg($pickupUrl, 200);
        $tags = implode(', ', $this->deposit->bag_tags ?? []);

        return (new MailMessage)
            ->subject('Ritiro bagagli — QR code '.$this->deposit->code)
            ->greeting('Ciao '.$this->deposit->customer_name.',')
            ->line('Il deposito dei tuoi bagagli è attivo. Conserva questa email: ti servirà al momento del ritiro.')
            ->line('Codice deposito: '.$this->deposit->code)
            ->line('Tag bagagli: '.$tags)
            ->line('Al ritiro, mostra il QR code qui sotto allo sportello. Il personale lo scannerà per avviare la consegna.')
            ->line(new HtmlString(
                '<div style="text-align:center;margin:24px 0;">'.$qrSvg.'</div>'
            ))
            ->action('Apri pagina ritiro', $pickupUrl)
            ->line('Importante: usa questo QR solo dopo il check-in in agenzia. Non è valido per prenotazioni non ancora consegnate.');
    }
}
