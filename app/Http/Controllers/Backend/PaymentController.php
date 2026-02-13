<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\MieClassi\GeneraFattura;
use App\Http\MieClassi\StripeKey;
use App\Models\Carrello;
use App\Models\Fattura;
use App\Models\Pagamento;
use App\Models\MovimentoPortafoglio;
use App\Models\Richiesta;
use App\Models\ServizioAcquistato;
use App\Models\User;
use App\Notifications\ModuloRichiestaAComuneNotification;
use App\Notifications\PagamentoAvvenutoAAdminNotification;
use App\Notifications\PagamentoAvvenutoACittadinoNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use function App\calcolaImpostaIva;
use function App\getInputNumero;

class PaymentController extends Controller
{


    public function pagamento(Request $request, $servizio)
    {

        switch ($servizio) {
            case 'stripe':
                \Log::debug(__CLASS__ . ':' . __FUNCTION__ . 'documenti:' . $request->input('richiesta_da_pagare'), $request->input());
                return $this->createCheckoutSession($request);

        }

    }


    public function storePagamento(Request $request)
    {

        $importo = getInputNumero($request->input('importo'));
        if ($importo < 20) {
            return redirect()->back()->withErrors(['importo' => "L'importo deve essere superiore a €20"]);
        }

        $user = $request->user();
        $paymentMethod = $request->input('payment_method');

        $totale = $importo + 1;
        \Log::debug('iniziato ricarica portafoglio agente: ' . Auth::id() . ' per importo:' . $importo);
        try {
            $user->createOrGetStripeCustomer();
            $user->updateDefaultPaymentMethod($paymentMethod);
            $res = $user->charge($totale * 100, $paymentMethod);
            $pagamento = new Pagamento();
            $pagamento->servizio = 'stripe';
            $pagamento->agente_id = Auth::id();
            $pagamento->transaction_id = $res->id;
            $pagamento->descrizione = 'Pagamento ' . Auth::user()->nominativo();
            $pagamento->importo = $res->amount_received / 100;
            $pagamento->valuta = $res->currency;
            $pagamento->status = $res->payment_status ?? '';
            $pagamento->response = (array)$res;
            $pagamento->save();
            $movimento = new MovimentoPortafoglio();
            $movimento->agente_id = Auth::id();
            $movimento->importo = $importo;
            $movimento->descrizione = 'Ricarica Stripe ' . $pagamento->transaction_id;
            $movimento->portafoglio = $request->input('portafoglio');
            $movimento->save();
            \Log::info('Caricato portafoglio di:' . $importo);
        } catch (\Exception $exception) {
            \Log::alert('Errore ricarica stripe:' . $exception->getMessage());
            return back()->with('error', $exception->getMessage());
        }

        return redirect()->action([PaymentController::class, 'pagamentoSuccess']);
    }


    public function createCheckoutSession(Request $request)
    {
        \Stripe\Stripe::setApiKey(StripeKey::getSecretKey());

        $richiestaId = $request->input('richiesta_da_pagare');

        $richiesta = Richiesta::withCount('documenti')->find($richiestaId);

        abort_if($richiesta->pagamento_id, 404, 'Questa richiesta risulta pagata');
        abort_if(!$richiesta, 404, 'Questa richiesta non esiste');
        $imponibile = config('configurazione.prezzoDocumento') * $richiesta->documenti_count;
        $imposta = calcolaImpostaIva($imponibile, config('configurazione.aliquotaIva'));
        $totale = $imponibile + $imposta;

        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            //'customer_email' => \Auth::user()->email,
            'line_items' => [

                [
                    'price_data' => [

                        'currency' => 'eur',
                        'product_data' => [
                            'name' => config('configurazione.descrizioneServizio'),
                            'metadata' => [
                                'id' => $richiestaId
                            ]
                        ],
                        'unit_amount' => $totale * 100,
                    ],
                    'quantity' => 1,
                ]],
            'mode' => 'payment',
            'success_url' => url()->to(action([PaymentController::class, 'response'], ['servizio' => 'stripe', 'result' => 'success'])),
            'cancel_url' => url()->to(action([PaymentController::class, 'response'], ['servizio' => 'stripe', 'result' => 'failed'])),
            'client_reference_id' => \Auth::id(),


        ]);
        \Log::debug(__CLASS__ . '::' . __FUNCTION__, (array)$checkout_session);

        session()->put('stripeId', ['checkoutSessionId' => $checkout_session->id, 'id_documenti' => $richiestaId]);
        return ['id' => $checkout_session->id];
    }


    public function response($servizio, $result)
    {
        switch ($servizio) {
            case 'stripe':
                switch ($result) {
                    case 'success':
                        return $this->stripeSuccess($servizio);

                    case 'failed':
                        return $this->stripeFailed();
                }
        }

        abort(404);
    }

    public function pagamentoSuccess()
    {
        return view('Backend.Portafoglio.esito',
            [
                'titoloPagina' => 'Esito pagamento',
                'success' => true,
                'breadcrumbs' => [action([PortafoglioController::class, 'index']) => 'Torna a elenco movimenti'],
            ]);

    }


    public function stripeSuccess($servizio)
    {
        \Log::debug(__CLASS__ . '::' . __FUNCTION__);

        $stripe = new \Stripe\StripeClient(
            StripeKey::getSecretKey()
        );

        $stripeId = session()->get('stripeId');
// ['checkoutSessionId' => $checkout_session->id, 'tipoAbbonamentoId' => $abbonamento->id]
        if ($stripeId) {
            $res = $stripe->checkout->sessions->retrieve(
                $stripeId['checkoutSessionId'],
                []
            );


            $clientReference = $res->client_reference_id;

            $user = User::find($clientReference);


            if ($user) {
                $pagamento = Pagamento::where('transaction_id', $res->payment_intent)->first();
                if (!$pagamento) {
                    return $this->inserisciPagamento($user, $stripeId, $res);
                } else {
                    return view('Frontend.Pagamento.ripetuto');
                }
            }

            session()->forget('stripeId');
        }
        return view('Frontend.Pagamento.failed', [

        ]);

    }


    public function stripeFailed()
    {

        \Log::debug(__CLASS__ . '::' . __FUNCTION__);

        $stripe = new \Stripe\StripeClient(
            StripeKey::getSecretKey()
        );

        $stripeId = session()->get('stripeId');

        \Log::debug('Pagamento failed');
        if ($stripeId) {
            $res = $stripe->checkout->sessions->retrieve(
                $stripeId['checkoutSessionId'],
                []
            );


            $clientReference = $res->client_reference_id;

            $user = User::find($clientReference);


            if ($user) {
                $documentiIdStr = $stripeId['id_documenti'] ?? false;

                $pagamento = new Pagamento();
                $pagamento->user_id = $user->id;
                $pagamento->importo = $res->amount_total / 100;
                $pagamento->valuta = $res->currency;
                $pagamento->transaction_id = $res->payment_intent;
                $pagamento->status = $res->payment_status;
                $pagamento->servizio = 'stripe';
                $pagamento->descrizione = 'Pagamento richieste documento: ' . $documentiIdStr;
                $pagamento->response = (array)$res;

                $pagamento->save();

                \Log::warning(__CLASS__ . '::' . __FUNCTION__, (array)$res);


            }


        }

        return view('Frontend.Pagamento.failed', [

        ]);

    }

}
