<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;
use Throwable;

class EmailLogger
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function handle(MessageSent $event)
    {
        try {
            $message = $event->message;
            if (!$message instanceof Email) {
                return;
            }

            $allegati = [];
            $attachments = $message->getAttachments();
            if (is_iterable($attachments)) {
                foreach ($attachments as $att) {
                    $filename = method_exists($att, 'getFilename') ? $att->getFilename() : null;
                    if ($filename) {
                        $allegati[] = $filename;
                    }
                }
            }

            $htmlBody = $message->getHtmlBody();
            $textBody = $message->getTextBody();
            $body = $htmlBody ?: $textBody ?: '';

            DB::table('registro_email')->insert([
                'data' => now()->format('Y-m-d H:i:s'),
                'from' => Str::limit($this->formatAddressField($message->getFrom()), 250, ''),
                'to' => Str::limit($this->formatAddressField($message->getTo()), 250, ''),
                'cc' => Str::limit($this->formatAddressField($message->getCc()), 250, ''),
                'bcc' => Str::limit($this->formatAddressField($message->getBcc()), 250, ''),
                'subject' => Str::limit((string)($message->getSubject() ?? '(senza oggetto)'), 250, ''),
                'body' => base64_encode(gzcompress((string)$body, 9)),
                'attachments' => implode(', ', $allegati),
            ]);
        } catch (Throwable $e) {
            Log::warning('EmailLogger: impossibile salvare su registro_email', [
                'errore' => $e->getMessage(),
            ]);
            return;
        }
    }

    /**
     * @param iterable<\Symfony\Component\Mime\Address>|null $addresses
     * @return string
     */
    protected function formatAddressField($addresses): string
    {
        if (!is_iterable($addresses)) {
            return '';
        }

        $strings = [];
        foreach ($addresses as $address) {
            if (method_exists($address, 'getAddress')) {
                $mailboxStr = (string)$address->getAddress();
                if ($mailboxStr !== '') {
                    $strings[] = $mailboxStr;
                }
            }
        }

        return implode(', ', $strings);
    }

}
