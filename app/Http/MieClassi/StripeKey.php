<?php

namespace App\Http\MieClassi;

class StripeKey
{
    public static function getPublicKey()
    {
        return config('services.stripe.key')
            ?: config('cashier.key')
            ?: config('configurazione.STRIPE_PUBLIC_KEY');
    }

    public static function getSecretKey()
    {
        return config('services.stripe.secret')
            ?: config('cashier.secret')
            ?: config('configurazione.STRIPE_SECRET_KEY');
    }
}
