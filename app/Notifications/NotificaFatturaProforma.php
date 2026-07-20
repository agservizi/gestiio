<?php

namespace App\Notifications;

use App\Models\FatturaProforma;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificaFatturaProforma extends Notification
{
    use Queueable;

    public function __construct(
        protected FatturaProforma $fattura,
        protected string $pdfBinary,
        protected string $filename
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $numero = $this->fattura->numero;
        $anno = optional($this->fattura->data)->format('Y') ?? date('Y');
        $periodo = $this->fattura->periodoLabel();

        $mail = (new MailMessage)
            ->subject('Fattura proforma #'.$numero.'/'.$anno)
            ->greeting('Ciao '.$notifiable->nominativo().',')
            ->line('In allegato trovi la fattura proforma #'.$numero.' del '.$this->fattura->data->format('d/m/Y').'.')
            ->line('Totale: '.importo($this->fattura->totale_con_iva, true).'.');

        if ($periodo) {
            $mail->line('Periodo di competenza: '.$periodo.'.');
        }

        $mail->salutation(config('mail.from.name', 'Gestiio'));

        $mail->attachData($this->pdfBinary, $this->filename, [
            'mime' => 'application/pdf',
        ]);

        return $mail;
    }
}
