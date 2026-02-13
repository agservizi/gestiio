<?php

namespace App\Listeners;

use DB;
use Illuminate\Auth\Events\Login;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\MailboxListHeader;

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

    /**
     * Handle the event.
     *
     * @param Login $event
     * @return void
     */
    public function handle(MessageSent $event)
    {
        //
        $message = $event->message;


        $allegati = [];
        if ($message->getAttachments()) {
            foreach ($message->getAttachments() as $att) {
                $allegati[] = $att->getFilename();
            }
        }


        DB::table('registro_email')->insert([
            'data' => date('Y-m-d H:i:s'),
            'from' => $this->formatAddressField($message->getFrom()),
            'to' => $this->formatAddressField($message->getTo()),
            'cc' => $this->formatAddressField($message->getCc()),
            'bcc' => $this->formatAddressField($message->getBcc()),
            'subject' => $message->getSubject(),
            'body' => base64_encode(gzcompress($message->getHtmlBody(), 9)),
            //'headers' => (string)$message->getHeaders(),
            'attachments' => implode(', ', $allegati),
        ]);
    }

    /**
     * Format address strings for sender, to, cc, bcc.
     *
     * @param Address $message
     * @param $field
     * @return null|string
     */
    function formatAddressField($addresses)
    {


        $strings = [];
        foreach ($addresses as $address) {

            $mailboxStr = $address->getAddress();
            $strings[] = $mailboxStr;
        }
        return implode(', ', $strings);
    }

}
