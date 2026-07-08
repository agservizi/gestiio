<?php

namespace App\Mail;

use App\Models\Notifica;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificaMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(protected Notifica $notifica)
    {
        //
    }

    /**
     * Get the message envelope.
     *
     * @return Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->notifica->titolo,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.gestiio-notification',
            with: [
                'subject' => $this->notifica->titolo,
                'kicker' => 'Notifica',
                'title' => $this->notifica->titolo,
                'preheader' => $this->notifica->titolo,
                'tone' => $this->notifica->tipo ?: 'info',
                'image' => $this->notifica->urlImmagine() ? url($this->notifica->urlImmagine()) : null,
                'bodyHtml' => $this->notifica->testo,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
