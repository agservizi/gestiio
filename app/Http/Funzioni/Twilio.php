<?php

namespace App\Http\Funzioni;

use Twilio\Rest\Client;

class Twilio
{
    public static function oneWay($destinatario, $messaggio)
    {
        try {

            //sandbox
            $sid = env('TWILIO_SID');
            $token = env('TWILIO_TOKEN');

            //api key
            $sid = env('TWILIO_SID');
            $token = env('TWILIO_TOKEN');

            //live
            $sid = env('TWILIO_SID');
            $token = env('TWILIO_TOKEN');


            $twilio = new Client($sid, $token);

            $twilio->setLogLevel('debug');

            $message = $twilio->messages
                ->create("whatsapp:$destinatario", // to
                    array(
                        "from" => "whatsapp:+14155238886",
                        "body" => $messaggio
                    )
                );
            //print('messageId:' . $message->sid);
            return $message->sid . '-' . $message->status;
        } catch (\Exception $e) {
            dd("Error: " . $e->getMessage());
        }
    }

    public static function sms($destinatario, $messaggio)
    {
        try {

            //sandbox
            $sid = env('TWILIO_SID');
            $token = env('TWILIO_TOKEN');

            //api key
            $sid = env('TWILIO_SID');
            $token = env('TWILIO_TOKEN');

            //live
            $sid = env('TWILIO_SID');
            $token = env('TWILIO_TOKEN');

            $sender = '+14798471668';
            $sender = '+393400948048';


            $twilio = new Client($sid, $token);

            $twilio->setLogLevel('debug');

            $message = $twilio->messages
                ->create("$destinatario", // to
                    array(
                        "body" => $messaggio,
                        // 'messagingServiceSid' => $sid,
                        'from' => '+14798471668'
                    )
                );
            //print('messageId:' . $message->sid);
            return $message->sid . '-' . $message->status;
        } catch (\Exception $e) {
            dd("Error: " . $e->getMessage());
        }
    }
}
