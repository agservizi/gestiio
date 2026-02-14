<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\MieClassi\GeneraFattura;
use App\Http\MieClassi\StripeKey;
use App\Models\Carrello;
use App\Models\Fattura;
use App\Models\Pagamento;
use App\Models\MovimentoPortafoglio;
use App\Models\ServizioAcquistato;
use App\Models\User;
use App\Notifications\ModuloRichiestaAComuneNotification;
use App\Notifications\PagamentoAvvenutoAAdminNotification;
use App\Notifications\PagamentoAvvenutoACittadinoNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Exception\ApiErrorException;
use function App\calcolaImposta;
use function App\getInputNumero;

class PaymentController extends Controller
{


    public function pagamento(Request $request, $servizio)
    {

        switch ($servizio) {
            case 'stripe':
                Log::debug(__CLASS__ . ':' . __FUNCTION__ . 'documenti:' . $request->input('richiesta_da_pagare'), $request->input());
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

        if (!$paymentMethod) {
            return back()->with('error', 'Metodo di pagamento non valido. Inserisci nuovamente i dati della carta e riprova.');
        }

        $totale = $importo + 1;
        Log::debug('iniziato ricarica portafoglio agente: ' . Auth::id() . ' per importo:' . $importo);
        try {
            /** @var User $user */
            $user->createOrGetStripeCustomer();
            $user->updateDefaultPaymentMethod($paymentMethod);
            $res = $user->charge($totale * 100, $paymentMethod);
            /** @var User $authUser */
            $authUser = Auth::user();
            $pagamento = new Pagamento();
            $pagamento->servizio = 'stripe';
            $pagamento->agente_id = Auth::id();
            $pagamento->transaction_id = $res->id;
            $pagamento->descrizione = 'Pagamento ' . $authUser->nominativo();
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
            Log::info('Caricato portafoglio di:' . $importo);
        } catch (IncompletePayment $exception) {
            Log::warning('Pagamento incompleto ricarica stripe', [
                'agente_id' => Auth::id(),
                'importo' => $importo,
                'message' => $exception->getMessage(),
            ]);
            return back()->with('error', 'Pagamento non completato. Verifica con la tua banca (3D Secure) e riprova.');
        } catch (ApiErrorException $exception) {
            $errorContext = $this->stripeErrorContext($exception);
            Log::alert('Errore Stripe API ricarica', [
                'agente_id' => Auth::id(),
                'importo' => $importo,
                'message' => $exception->getMessage(),
                'stripe_code' => $errorContext['code'],
                'stripe_decline_code' => $errorContext['decline_code'],
                'stripe_type' => $errorContext['type'],
            ]);
            return back()->with('error', $this->stripeErrorMessageFromCode($errorContext['code']));
        } catch (\Exception $exception) {
            Log::alert('Errore ricarica stripe:' . $exception->getMessage());
            return back()->with('error', 'Pagamento non riuscito. Controlla i dati della carta e riprova.');
        }

        return redirect()->action([PaymentController::class, 'pagamentoSuccess']);
    }


    public function createCheckoutSession(Request $request)
    {
        \Stripe\Stripe::setApiKey(StripeKey::getSecretKey());

        $richiestaId = $request->input('richiesta_da_pagare');
        $richiestaClass = '\\App\\Models\\Richiesta';

        abort_if(!class_exists($richiestaClass), 404, 'Modulo richieste non disponibile');

        $richiesta = $richiestaClass::withCount('documenti')->find($richiestaId);

        abort_if(!$richiesta, 404, 'Questa richiesta non esiste');
        abort_if($richiesta->pagamento_id, 404, 'Questa richiesta risulta pagata');
        $imponibile = config('configurazione.prezzoDocumento') * $richiesta->documenti_count;
        $imposta = calcolaImposta($imponibile, config('configurazione.aliquota_iva'));
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
            'client_reference_id' => Auth::id(),


        ]);
        Log::debug(__CLASS__ . '::' . __FUNCTION__, (array)$checkout_session);

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
        Log::debug(__CLASS__ . '::' . __FUNCTION__);

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

        Log::debug(__CLASS__ . '::' . __FUNCTION__);

        $stripe = new \Stripe\StripeClient(
            StripeKey::getSecretKey()
        );

        $stripeId = session()->get('stripeId');

        Log::debug('Pagamento failed');
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

                Log::warning(__CLASS__ . '::' . __FUNCTION__, (array)$res);


            }


        }

        return view('Frontend.Pagamento.failed', [

        ]);

    }

    protected function stripeErrorContext(ApiErrorException $exception): array
    {
        $error = method_exists($exception, 'getError') ? $exception->getError() : null;

        $code = $exception->getStripeCode();
        $declineCode = null;
        $type = null;

        if (is_object($error)) {
            $code = $error->code ?? $code;
            $declineCode = $error->decline_code ?? null;
            $type = $error->type ?? null;
        }

        if (!$code) {
            $code = $declineCode;
        }

        return [
            'code' => $code ? strtolower((string)$code) : null,
            'decline_code' => $declineCode ? strtolower((string)$declineCode) : null,
            'type' => $type ? strtolower((string)$type) : null,
        ];
    }

    protected function stripeErrorMessageFromCode(?string $code): string
    {
        switch ($code) {
            case 'card_declined':
            case 'do_not_honor':
            case 'generic_decline':
                return 'Carta rifiutata dalla banca. Prova un\'altra carta o contatta la banca.';

            case 'lost_card':
            case 'stolen_card':
            case 'pickup_card':
            case 'restricted_card':
                return 'La carta non può essere utilizzata per questo pagamento. Usa un\'altra carta.';

            case 'insufficient_funds':
                return 'Fondi insufficienti sulla carta. Usa un altro metodo di pagamento.';

            case 'expired_card':
                return 'Carta scaduta. Inserisci una carta valida.';

            case 'incorrect_number':
            case 'invalid_number':
                return 'Numero carta non valido. Controlla i dati e riprova.';

            case 'incorrect_cvc':
            case 'invalid_cvc':
                return 'CVC non corretto. Controlla il codice di sicurezza e riprova.';

            case 'incorrect_zip':
            case 'invalid_expiry_month':
            case 'invalid_expiry_year':
                return 'Dati carta non validi. Verifica scadenza e dati di fatturazione.';

            case 'amount_too_large':
                return 'Importo troppo alto per questa operazione. Prova con un importo inferiore.';

            case 'amount_too_small':
                return 'Importo troppo basso per questa operazione.';

            case 'processing_error':
            case 'api_connection_error':
            case 'api_error':
            case 'rate_limit':
                return 'Errore temporaneo del circuito di pagamento. Riprova tra qualche minuto.';

            case 'authentication_required':
                return 'Autenticazione richiesta dalla banca. Completa la verifica 3D Secure e riprova.';

            case 'payment_intent_authentication_failure':
                return 'Autenticazione del pagamento non riuscita. Riprova e completa la verifica richiesta.';

            default:
                return 'Pagamento non riuscito. Controlla i dati della carta e riprova.';
        }
    }

}
