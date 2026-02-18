<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateChatVapidKeys extends Command
{
    protected $signature = 'chat:generate-vapid-keys';

    protected $description = 'Genera le chiavi VAPID per le notifiche Web Push della chat';

    public function handle(): int
    {
        $keys = $this->generateKeys();

        if (!$keys) {
            $this->error('Impossibile generare chiavi VAPID. Verifica dipendenze e OpenSSL.');
            $this->line('Suggerimenti:');
            $this->line('- composer require minishlink/web-push:^9.0');
            $this->line('- composer dump-autoload -o');
            $this->line('- php -m | grep openssl');
            return self::FAILURE;
        }

        $this->info('Chiavi VAPID generate. Inserisci queste variabili nel tuo .env:');
        $this->line('WEBPUSH_VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('WEBPUSH_VAPID_PRIVATE_KEY=' . $keys['privateKey']);
        $this->line('WEBPUSH_VAPID_SUBJECT=mailto:dev@example.com');

        return self::SUCCESS;
    }

    protected function generateKeys(): ?array
    {
        if (class_exists('\\Minishlink\\WebPush\\VAPID')) {
            return \Minishlink\WebPush\VAPID::createVapidKeys();
        }

        if (!function_exists('openssl_pkey_new')) {
            return null;
        }

        $resource = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);

        if (!$resource) {
            return null;
        }

        $details = openssl_pkey_get_details($resource);
        $x = $details['ec']['x'] ?? null;
        $y = $details['ec']['y'] ?? null;
        $d = $details['ec']['d'] ?? null;

        if (!$x || !$y || !$d) {
            return null;
        }

        $publicKey = "\x04" . $x . $y;

        return [
            'publicKey' => $this->base64UrlEncode($publicKey),
            'privateKey' => $this->base64UrlEncode($d),
        ];
    }

    protected function base64UrlEncode(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }
}
