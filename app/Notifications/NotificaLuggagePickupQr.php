<?php

namespace App\Notifications;

use App\Http\Support\LuggageBilingualMail;
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
            ->subject(LuggageBilingualMail::subject(
                'Luggage pickup — QR code',
                'Ritiro bagagli — codice QR',
                $this->deposit->code
            ))
            ->greeting(LuggageBilingualMail::greeting(
                $this->deposit->customer_name,
                'Hello',
                'Ciao'
            ))
            ->line(LuggageBilingualMail::line(
                'Your scheduled pickup time is approaching. Show the QR code below at the desk to collect your luggage.',
                'Si avvicina la data di ritiro prevista. Mostra il QR code qui sotto allo sportello per ritirare i bagagli.'
            ))
            ->line(LuggageBilingualMail::line(
                'Booking code: '.$this->deposit->code,
                'Codice prenotazione: '.$this->deposit->code
            ))
            ->line(LuggageBilingualMail::line(
                'Expected pickup: '.($this->deposit->expected_check_out?->format('d/m/Y H:i') ?? '—'),
                'Ritiro previsto: '.($this->deposit->expected_check_out?->format('d/m/Y H:i') ?? '—')
            ))
            ->line(LuggageBilingualMail::line(
                'Bag tags: '.$tags,
                'Tag bagagli: '.$tags
            ))
            ->line(LuggageBilingualMail::line(
                'Staff will scan this QR code and the tags on your bags to complete the handover.',
                'Il personale scannerà questo QR e i tag sui bagagli per completare la consegna.'
            ))
            ->line(new HtmlString(
                '<div style="text-align:center;margin:24px 0;">'.$qrSvg.'</div>'
            ))
            ->action(
                LuggageBilingualMail::actionLabel('Open pickup page', 'Apri pagina ritiro'),
                $pickupUrl
            );
    }
}
