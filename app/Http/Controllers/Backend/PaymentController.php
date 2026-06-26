<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Enums\TipiPortafoglioEnum;
use App\Http\MieClassi\StripeKey;
use App\Models\MovimentoPortafoglio;
use App\Models\Pagamento;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\StripeClient;

use function App\calcolaImposta;
use function App\getInputNumero;

class PaymentController extends Controller
{
    public function pagamento(Request $request, $servizio)
    {

        switch ($servizio) {
            case 'stripe':
                Log::debug(__CLASS__.':'.__FUNCTION__.'documenti:'.$request->input('richiesta_da_pagare'), $request->input());

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

        if (! $paymentMethod) {
            return back()->with('error', 'Metodo di pagamento non valido. Inserisci nuovamente i dati della carta e riprova.');
        }

        $totale = $importo + 1;
        Log::debug('iniziato ricarica portafoglio agente: '.Auth::id().' per importo:'.$importo);
        try {
            /** @var User $user */
            $res = $this->addebitaRicaricaStripe($user, $paymentMethod, $totale);
            /** @var User $authUser */
            $authUser = Auth::user();
            $pagamento = new Pagamento;
            $pagamento->servizio = 'stripe';
            $pagamento->agente_id = Auth::id();
            $pagamento->transaction_id = $res->id;
            $pagamento->descrizione = 'Pagamento '.$authUser->nominativo();
            $pagamento->importo = $res->amount_received / 100;
            $pagamento->valuta = $res->currency;
            $pagamento->status = $res->payment_status ?? '';
            $pagamento->response = (array) $res;
            $pagamento->save();
            $movimento = new MovimentoPortafoglio;
            $movimento->agente_id = Auth::id();
            $movimento->importo = $importo;
            $movimento->descrizione = 'Ricarica Stripe '.$pagamento->transaction_id;
            $movimento->portafoglio = $request->input('portafoglio');
            $movimento->save();
            Log::info('Caricato portafoglio di:'.$importo);
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
            Log::alert('Errore ricarica stripe:'.$exception->getMessage());

            return back()->with('error', 'Pagamento non riuscito. Controlla i dati della carta e riprova.');
        }

        return redirect()->action([PaymentController::class, 'pagamentoSuccess']);
    }

    public function prepareChatRicarica(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $stripePublicKey = StripeKey::getPublicKey();

        if (! $stripePublicKey || ! StripeKey::getSecretKey()) {
            return response()->json([
                'message' => 'La ricarica con carta non è configurata. Usa la pagina ricarica o riprova più tardi.',
            ], 422);
        }

        try {
            $user->createOrGetStripeCustomer();
            $intent = $user->createSetupIntent([
                'payment_method_types' => ['card'],
            ]);
        } catch (ApiErrorException $exception) {
            if (! $this->stripeCustomerNonTrovato($exception)) {
                Log::warning('Chat ricarica plafond: Stripe non disponibile', [
                    'user_id' => $user->id,
                    'error' => $exception->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Stripe non è disponibile adesso. Riprova tra qualche minuto.',
                ], 422);
            }

            $this->resetStripeCustomer($user);

            try {
                $user->createOrGetStripeCustomer();
                $intent = $user->createSetupIntent([
                    'payment_method_types' => ['card'],
                ]);
            } catch (\Throwable $exception) {
                Log::warning('Chat ricarica plafond: setup intent non creato', [
                    'user_id' => $user->id,
                    'error' => $exception->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Non riesco a preparare la ricarica adesso. Riprova tra poco.',
                ], 422);
            }
        } catch (\Throwable $exception) {
            Log::warning('Chat ricarica plafond: setup intent non creato', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Non riesco a preparare la ricarica adesso. Riprova tra poco.',
            ], 422);
        }

        return response()->json([
            'stripe_public_key' => $stripePublicKey,
            'client_secret' => $intent->client_secret,
            'holder_name' => $user->nominativo(),
            'amounts' => [
                ['value' => 20, 'label' => '20 euro'],
                ['value' => 50, 'label' => '50 euro'],
                ['value' => 100, 'label' => '100 euro'],
            ],
            'wallets' => array_map(static fn (TipiPortafoglioEnum $case) => [
                'value' => $case->value,
                'label' => $case->testo(),
            ], TipiPortafoglioEnum::cases()),
            'fee_label' => 'La commissione Stripe è di 1 euro.',
        ]);
    }

    public function storePagamentoChat(Request $request)
    {
        $request->validate([
            'importo' => ['required'],
            'portafoglio' => ['required', Rule::in(array_map(static fn (TipiPortafoglioEnum $case) => $case->value, TipiPortafoglioEnum::cases()))],
            'payment_method' => ['required', 'string'],
        ]);

        $importo = getInputNumero($request->input('importo'));
        if ($importo < 20) {
            return response()->json([
                'message' => "L'importo deve essere almeno di 20 euro.",
            ], 422);
        }

        /** @var User $user */
        $user = $request->user();
        $paymentMethod = (string) $request->input('payment_method');
        $totale = $importo + 1;

        Log::debug('iniziata ricarica portafoglio da chat: '.$user->id.' per importo:'.$importo);

        try {
            $res = $this->addebitaRicaricaStripe($user, $paymentMethod, $totale);

            $pagamento = new Pagamento;
            $pagamento->servizio = 'stripe';
            $pagamento->agente_id = $user->id;
            $pagamento->transaction_id = $res->id;
            $pagamento->descrizione = 'Pagamento '.$user->nominativo();
            $pagamento->importo = $res->amount_received / 100;
            $pagamento->valuta = $res->currency;
            $pagamento->status = $res->payment_status ?? '';
            $pagamento->response = (array) $res;
            $pagamento->save();

            $movimento = new MovimentoPortafoglio;
            $movimento->agente_id = $user->id;
            $movimento->importo = $importo;
            $movimento->descrizione = 'Ricarica Stripe '.$pagamento->transaction_id;
            $movimento->portafoglio = $request->input('portafoglio');
            $movimento->save();

            return response()->json([
                'success' => true,
                'message' => 'Ricarica completata. Il plafond è stato aggiornato.',
                'redirect_url' => action([PortafoglioController::class, 'index']),
            ]);
        } catch (IncompletePayment $exception) {
            Log::warning('Pagamento incompleto ricarica stripe da chat', [
                'agente_id' => $user->id,
                'importo' => $importo,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Pagamento non completato. Completa la verifica della banca e riprova.',
            ], 422);
        } catch (ApiErrorException $exception) {
            $errorContext = $this->stripeErrorContext($exception);
            Log::alert('Errore Stripe API ricarica da chat', [
                'agente_id' => $user->id,
                'importo' => $importo,
                'message' => $exception->getMessage(),
                'stripe_code' => $errorContext['code'],
                'stripe_decline_code' => $errorContext['decline_code'],
                'stripe_type' => $errorContext['type'],
            ]);

            return response()->json([
                'message' => $this->stripeErrorMessageFromCode($errorContext['code']),
            ], 422);
        } catch (\Exception $exception) {
            Log::alert('Errore ricarica stripe da chat:'.$exception->getMessage());

            return response()->json([
                'message' => 'Pagamento non riuscito. Controlla i dati della carta e riprova.',
            ], 422);
        }
    }

    public function storeBonificoStripe(Request $request)
    {
        $request->validate([
            'importo' => ['required'],
            'portafoglio' => ['required', Rule::in(array_map(static fn (TipiPortafoglioEnum $case) => $case->value, TipiPortafoglioEnum::cases()))],
        ]);

        $importo = getInputNumero($request->input('importo'));
        if ($importo < 20) {
            return redirect()->back()->withErrors(['importo' => "L'importo deve essere superiore a €20"]);
        }

        /** @var User $user */
        $user = $request->user();
        $portafoglio = $request->input('portafoglio');

        try {
            $intent = $this->creaPaymentIntentBonificoStripe($user, $importo, $portafoglio);

            $pagamento = Pagamento::where('transaction_id', $intent->id)->first() ?: new Pagamento;
            $pagamento->transaction_id = $intent->id;
            $pagamento->servizio = 'stripe_bank_transfer';
            $pagamento->agente_id = $user->id;
            $pagamento->descrizione = 'Ricarica Stripe bonifico '.$user->nominativo();
            $pagamento->importo = $importo;
            $pagamento->valuta = $intent->currency;
            $pagamento->status = $intent->status;
            $pagamento->response = $intent->toArray();
            $pagamento->save();

            return view('Backend.Portafoglio.bonificoStripeIstruzioni', [
                'titoloPagina' => 'Bonifico Stripe',
                'breadcrumbs' => [action([PortafoglioController::class, 'create']) => 'Torna a ricarica portafoglio'],
                'paymentIntent' => $intent,
                'istruzioni' => optional(optional($intent->next_action)->display_bank_transfer_instructions)->toArray(),
                'importo' => $importo,
                'portafoglio' => $portafoglio,
            ]);
        } catch (ApiErrorException $exception) {
            Log::alert('Errore Stripe bonifico ricarica', [
                'agente_id' => $user->id,
                'importo' => $importo,
                'message' => $exception->getMessage(),
                'stripe_code' => $this->stripeErrorContext($exception)['code'],
            ]);

            return back()->with('error', 'Bonifico Stripe non disponibile. Verifica che Bank transfer sia attivo in Stripe e riprova.');
        } catch (\Exception $exception) {
            Log::alert('Errore bonifico Stripe ricarica:'.$exception->getMessage());

            return back()->with('error', 'Bonifico Stripe non disponibile. Riprova tra qualche minuto.');
        }
    }

    public function createCheckoutSession(Request $request)
    {
        Stripe::setApiKey(StripeKey::getSecretKey());

        $richiestaId = $request->input('richiesta_da_pagare');
        $richiestaClass = '\\App\\Models\\Richiesta';

        abort_if(! class_exists($richiestaClass), 404, 'Modulo richieste non disponibile');

        $richiesta = $richiestaClass::withCount('documenti')->find($richiestaId);

        abort_if(! $richiesta, 404, 'Questa richiesta non esiste');
        abort_if($richiesta->pagamento_id, 404, 'Questa richiesta risulta pagata');
        $imponibile = config('configurazione.prezzoDocumento') * $richiesta->documenti_count;
        $imposta = calcolaImposta($imponibile, config('configurazione.aliquota_iva'));
        $totale = $imponibile + $imposta;

        $checkout_session = Session::create([
            'payment_method_types' => ['card'],
            // 'customer_email' => \Auth::user()->email,
            'line_items' => [

                [
                    'price_data' => [

                        'currency' => 'eur',
                        'product_data' => [
                            'name' => config('configurazione.descrizioneServizio'),
                            'metadata' => [
                                'id' => $richiestaId,
                            ],
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
        Log::debug(__CLASS__.'::'.__FUNCTION__, (array) $checkout_session);

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
        Log::debug(__CLASS__.'::'.__FUNCTION__);

        $stripe = new StripeClient(
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
                if (! $pagamento) {
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

        Log::debug(__CLASS__.'::'.__FUNCTION__);

        $stripe = new StripeClient(
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

                $pagamento = new Pagamento;
                $pagamento->user_id = $user->id;
                $pagamento->importo = $res->amount_total / 100;
                $pagamento->valuta = $res->currency;
                $pagamento->transaction_id = $res->payment_intent;
                $pagamento->status = $res->payment_status;
                $pagamento->servizio = 'stripe';
                $pagamento->descrizione = 'Pagamento richieste documento: '.$documentiIdStr;
                $pagamento->response = (array) $res;

                $pagamento->save();

                Log::warning(__CLASS__.'::'.__FUNCTION__, (array) $res);

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

        if (! $code) {
            $code = $declineCode;
        }

        return [
            'code' => $code ? strtolower((string) $code) : null,
            'decline_code' => $declineCode ? strtolower((string) $declineCode) : null,
            'type' => $type ? strtolower((string) $type) : null,
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

    protected function addebitaRicaricaStripe(User $user, string $paymentMethod, float $totale)
    {
        try {
            $user->createOrGetStripeCustomer();
            $user->updateDefaultPaymentMethod($paymentMethod);

            return $user->charge($totale * 100, $paymentMethod);
        } catch (ApiErrorException $exception) {
            if (! $this->stripeCustomerNonTrovato($exception)) {
                throw $exception;
            }

            $this->resetStripeCustomer($user);
            $user->createOrGetStripeCustomer();
            $user->updateDefaultPaymentMethod($paymentMethod);

            return $user->charge($totale * 100, $paymentMethod);
        }
    }

    protected function stripeCustomerNonTrovato(ApiErrorException $exception): bool
    {
        $code = method_exists($exception, 'getStripeCode') ? $exception->getStripeCode() : null;

        return $code === 'resource_missing'
            || str_contains(strtolower($exception->getMessage()), 'no such customer');
    }

    protected function resetStripeCustomer(User $user): void
    {
        $user->forceFill([
            'stripe_id' => null,
            'pm_type' => null,
            'pm_last_four' => null,
        ])->save();
    }

    protected function creaPaymentIntentBonificoStripe(User $user, float $importo, string $portafoglio)
    {
        $user->createOrGetStripeCustomer();

        $stripe = new StripeClient(StripeKey::getSecretKey());
        $params = [
            'amount' => (int) round($importo * 100),
            'currency' => 'eur',
            'customer' => $user->stripe_id,
            'payment_method_types' => ['customer_balance'],
            'payment_method_data' => ['type' => 'customer_balance'],
            'payment_method_options' => [
                'customer_balance' => [
                    'funding_type' => 'bank_transfer',
                    'bank_transfer' => [
                        'type' => 'eu_bank_transfer',
                        'eu_bank_transfer' => [
                            'country' => 'IT',
                        ],
                    ],
                ],
            ],
            'confirm' => true,
            'metadata' => [
                'tipo' => 'ricarica_portafoglio',
                'agente_id' => (string) $user->id,
                'importo' => (string) $importo,
                'portafoglio' => $portafoglio,
            ],
        ];

        try {
            return $stripe->paymentIntents->create($params);
        } catch (ApiErrorException $exception) {
            $code = method_exists($exception, 'getStripeCode') ? $exception->getStripeCode() : null;
            $message = strtolower($exception->getMessage());
            $countryNonSupportato = str_contains($message, 'country provided')
                && str_contains($message, 'is not supported')
                && str_contains($message, 'eu_bank_transfer');

            if (! $countryNonSupportato && ! in_array($code, ['parameter_invalid_enum', 'parameter_unknown'], true)) {
                throw $exception;
            }

            $params['payment_method_options']['customer_balance']['bank_transfer']['eu_bank_transfer']['country'] = 'IE';

            return $stripe->paymentIntents->create($params);
        }
    }
}
