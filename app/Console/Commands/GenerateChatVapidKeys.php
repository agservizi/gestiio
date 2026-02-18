<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateChatVapidKeys extends Command
{
    protected $signature = 'chat:generate-vapid-keys';

    protected $description = 'Genera le chiavi VAPID per le notifiche Web Push della chat';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->info('Chiavi VAPID generate. Inserisci queste variabili nel tuo .env:');
        $this->line('WEBPUSH_VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('WEBPUSH_VAPID_PRIVATE_KEY=' . $keys['privateKey']);
        $this->line('WEBPUSH_VAPID_SUBJECT=mailto:dev@example.com');

        return self::SUCCESS;
    }
}
