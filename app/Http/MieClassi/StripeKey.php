<?php


namespace App\Http\MieClassi;


class StripeKey
{
    public static function getPublicKey()
    {
        return env('STRIPE_KEY')
            ?: env('STRIPE_PUBLIC_KEY')
            ?: config('cashier.key')
            ?: config('configurazione.STRIPE_PUBLIC_KEY');
    }

    public static function getSecretKey()
    {
        return env('STRIPE_SECRET')
            ?: env('STRIPE_SECRET_KEY')
            ?: config('cashier.secret')
            ?: config('configurazione.STRIPE_SECRET_KEY');
    }
}
