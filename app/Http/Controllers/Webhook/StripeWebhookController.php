<?php

namespace App\Http\Controllers\Webhook;

use App\Enums\TipiPortafoglioEnum;
use App\Http\Controllers\Controller;
use App\Models\MovimentoPortafoglio;
use App\Models\Pagamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        if (! $secret) {
            Log::alert('Webhook Stripe non configurato: STRIPE_WEBHOOK_SECRET mancante');

            return response('Stripe webhook not configured', 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException|SignatureVerificationException $exception) {
            Log::warning('Webhook Stripe non valido', ['error' => $exception->getMessage()]);

            return response('Invalid Stripe webhook', 400);
        }

        switch ($event->type) {
            case 'payment_intent.requires_action':
            case 'payment_intent.partially_funded':
                $this->aggiornaPagamento($event->data->object);
                break;

            case 'payment_intent.succeeded':
                $this->accreditaRicaricaPortafoglio($event->data->object);
                break;
        }

        return response('ok');
    }

    protected function aggiornaPagamento(PaymentIntent $intent): void
    {
        if (($intent->metadata->tipo ?? null) !== 'ricarica_portafoglio') {
            return;
        }

        Pagamento::where('transaction_id', $intent->id)->update([
            'status' => $intent->status,
            'response' => $intent->toArray(),
        ]);
    }

    protected function accreditaRicaricaPortafoglio(PaymentIntent $intent): void
    {
        if (($intent->metadata->tipo ?? null) !== 'ricarica_portafoglio') {
            return;
        }

        DB::transaction(function () use ($intent) {
            $pagamento = Pagamento::where('transaction_id', $intent->id)->lockForUpdate()->first();

            if ($pagamento && $pagamento->status === 'succeeded') {
                return;
            }

            $agenteId = (int) ($intent->metadata->agente_id ?? 0);
            $portafoglio = (string) ($intent->metadata->portafoglio ?? TipiPortafoglioEnum::SERVIZI->value);
            if (! TipiPortafoglioEnum::tryFrom($portafoglio)) {
                $portafoglio = TipiPortafoglioEnum::SERVIZI->value;
            }

            $importo = (float) ($intent->metadata->importo ?? 0);
            if ($importo <= 0) {
                $importo = (float) (($intent->amount_received ?: $intent->amount) / 100);
            }

            if (! $pagamento) {
                $pagamento = new Pagamento;
                $pagamento->servizio = 'stripe_bank_transfer';
                $pagamento->agente_id = $agenteId;
                $pagamento->transaction_id = $intent->id;
                $pagamento->descrizione = 'Ricarica Stripe bonifico';
                $pagamento->importo = $importo;
                $pagamento->valuta = $intent->currency;
            }

            $pagamento->status = $intent->status;
            $pagamento->response = $intent->toArray();
            $pagamento->save();

            $movimento = new MovimentoPortafoglio;
            $movimento->agente_id = $agenteId;
            $movimento->importo = $importo;
            $movimento->descrizione = 'Ricarica Stripe bonifico '.$intent->id;
            $movimento->portafoglio = $portafoglio;
            $movimento->save();
        });
    }
}
