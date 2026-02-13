<?php


namespace App\Http\MieClassi;


class StripeKey
{
    public static function getPublicKey()
    {
        if (env('APP_ENV') == 'local') {
            return env('STRIPE_PUBLIC_KEY');
        } else {
            return config('configurazione.STRIPE_PUBLIC_KEY');
        }
    }

    public static function getSecretKey()
    {
        if (env('APP_ENV') == 'local') {
            return env('STRIPE_SECRET_KEY');
        } else {
            return config('configurazione.STRIPE_SECRET_KEY');
        }
    }
}
