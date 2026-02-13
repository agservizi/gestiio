<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Http;

class OpenApiSmsService
{
    protected $client;
    const ENDPOINT = 'https://test.ws.messaggisms.com/';

    public function __construct()
    {

        $this->client = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openapi.bearer_sms'),
            'content-type' => 'application/json'
        ]);
    }

    public function sendMessage($body, $recipients)
    {

        $dati = [
            'test' => false,
            'sender' => 'AG SERVIZI VIA PLINIO 72',
            'body' => $body,
            'recipients' => $recipients,
        ];

        $res = $this->client->post($this->endpoint() . 'messages/', $dati);
        return $res;
    }


    protected function endpoint(): string
    {
        if (!config('services.openapi.sandbox')) {
            return str_replace('test.', '', self::ENDPOINT);
        } else {
            return self::ENDPOINT;
        }
    }
}
