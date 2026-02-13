<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;

trait UsesPersonalizedMailSender
{
    protected function applyPersonalizedSender(MailMessage $email, $sender): MailMessage
    {
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');

        if ($sender) {
            if (method_exists($sender, 'nominativo')) {
                $senderName = $sender->nominativo();
            } else {
                $senderName = trim(($sender->nome ?? '') . ' ' . ($sender->cognome ?? ''));
            }

            if ($senderName !== '') {
                $fromName = $senderName;
            }

            $senderEmail = $sender->email ?? null;
            if ($senderEmail) {
                $email->replyTo($senderEmail, $fromName);
            }
        }

        return $email->from($fromAddress, $fromName);
    }
}
