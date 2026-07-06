<?php

namespace App\Http\Controllers\Backend;

use App\Enums\TipiPortafoglioEnum;
use App\Http\Controllers\Controller;
use App\Http\MieClassi\StripeKey;
use App\Models\Agente;
use App\Models\MovimentoPortafoglio;
use App\Models\Notifica;
use App\Models\RichiestaSpostamentoPortafoglio;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;
use Throwable;
use function App\getInputNumero;
use function App\importo;

class PortafoglioController extends Controller
{
    protected $conFiltro = false;

    /**
     * Display a listing of the resource.
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        $nomeClasse = get_class($this);
        $recordsQB = $this->applicaFiltri($request);
        /** @var User $authUser */
        $authUser = Auth::user();

        $ordinamenti = [
            'recente' => ['testo' => 'Più recente', 'filtro' => function ($q) {
                return $q->orderBy('id', 'desc');
            }],

            'nominativo' => ['testo' => 'Nominativo', 'filtro' => function ($q) {
                return $q->orderBy('cognome')->orderBy('nome');
            }],

        ];

        $orderByUser = $authUser->getExtra($nomeClasse);
        $orderByString = $request->input('orderBy');

        if ($orderByString) {
            $orderBy = $orderByString;
        } elseif ($orderByUser) {
            $orderBy = $orderByUser;
        } else {
            $orderBy = 'recente';
        }

        if ($orderByUser != $orderByString) {
            $authUser->setExtra([$nomeClasse => $orderBy]);
        }

        // Applico ordinamento
        $recordsQB = call_user_func($ordinamenti[$orderBy]['filtro'], $recordsQB);

        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        if ($request->ajax()) {

            return [
                'html' => base64_encode(view('Backend.Portafoglio.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ])->render()),
            ];

        }

        return view('Backend.Portafoglio.index', [
            'records' => $records,
            'richiesteSpostamento' => $authUser->hasPermissionTo('admin')
                ? RichiestaSpostamentoPortafoglio::with('agente')->where('stato', 'in_attesa')->orderByDesc('id')->get()
                : collect(),
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco movimenti',
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Carica '.MovimentoPortafoglio::NOME_SINGOLARE,
            'testoCerca' => null,

        ]);

    }

    /**
     * @param  Request  $request
     * @return Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = MovimentoPortafoglio::query();
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',nome)'), 'like', "%$t%");
            }
        }

        // $this->conFiltro = true;
        return $queryBuilder;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return mixed
     */
    public function create()
    {
        $record = new MovimentoPortafoglio;
        /** @var User $authUser */
        $authUser = Auth::user();
        $intent = null;
        $stripeErrore = null;
        $stripePublicKey = StripeKey::getPublicKey();

        if ($stripePublicKey && StripeKey::getSecretKey()) {
            try {
                $authUser->createOrGetStripeCustomer();
                $intent = $authUser->createSetupIntent([
                    'payment_method_types' => ['card'],
                ]);
            } catch (ApiErrorException|Throwable $exception) {
                if ($exception instanceof ApiErrorException && $this->stripeCustomerNonTrovato($exception)) {
                    $this->resetStripeCustomer($authUser);

                    try {
                        $authUser->createOrGetStripeCustomer();
                        $intent = $authUser->createSetupIntent([
                            'payment_method_types' => ['card'],
                        ]);
                    } catch (ApiErrorException|Throwable $exception) {
                        Log::warning('Ricarica portafoglio: Stripe non disponibile', [
                            'user_id' => $authUser->id,
                            'error' => $exception->getMessage(),
                        ]);
                        $stripeErrore = 'Ricarica con carta momentaneamente non disponibile. Usa il bonifico oppure aggiorna le credenziali Stripe.';
                    }
                } else {
                    Log::warning('Ricarica portafoglio: Stripe non disponibile', [
                        'user_id' => $authUser->id,
                        'error' => $exception->getMessage(),
                    ]);
                    $stripeErrore = 'Ricarica con carta momentaneamente non disponibile. Usa il bonifico oppure aggiorna le credenziali Stripe.';
                }
            }
        } else {
            $stripeErrore = 'Ricarica con carta non configurata. Usa il bonifico oppure configura le credenziali Stripe.';
        }

        return view('Backend.Portafoglio.edit', [
            'record' => $record,
            'titoloPagina' => 'Carica '.MovimentoPortafoglio::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([PortafoglioController::class, 'index']) => 'Torna a elenco movimenti'],
            'intent' => $intent,
            'stripePublicKey' => $stripePublicKey,
            'stripeErrore' => $stripeErrore,

        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return mixed
     */
    public function store(Request $request)
    {
        $request->validate($this->rules(null));
        $record = new MovimentoPortafoglio;
        $this->salvaDati($record, $request);

        return $this->backToIndex();
    }

    public function richiediSpostamento(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        abort_unless($authUser?->hasPermissionTo('agente'), 403);

        $wallets = array_map(static fn (TipiPortafoglioEnum $case) => $case->value, TipiPortafoglioEnum::cases());

        $request->validate([
            'portafoglio_da' => ['required', Rule::in($wallets)],
            'portafoglio_a' => ['required', Rule::in($wallets), 'different:portafoglio_da'],
            'importo' => ['required'],
            'descrizione' => ['required', 'max:255'],
        ], [
            'portafoglio_a.different' => 'Seleziona due portafogli diversi.',
        ]);

        $importo = (float) getInputNumero($request->input('importo'));

        if ($importo <= 0) {
            throw ValidationException::withMessages([
                'importo' => 'Inserisci un importo maggiore di zero.',
            ]);
        }

        $agente = Agente::where('user_id', $authUser->id)->first();
        if (! $agente) {
            throw ValidationException::withMessages([
                'portafoglio_da' => 'Profilo agente non trovato.',
            ]);
        }

        if ($this->saldoPortafoglio($agente, $request->input('portafoglio_da')) < $importo) {
            throw ValidationException::withMessages([
                'importo' => 'Saldo insufficiente nel portafoglio di partenza.',
            ]);
        }

        $richiesta = new RichiestaSpostamentoPortafoglio;
        $richiesta->agente_id = $authUser->id;
        $richiesta->portafoglio_da = $request->input('portafoglio_da');
        $richiesta->portafoglio_a = $request->input('portafoglio_a');
        $richiesta->importo = $importo;
        $richiesta->descrizione = trim((string) $request->input('descrizione'));
        $richiesta->save();

        $testo = '<p>'.$authUser->nominativo().' richiede lo spostamento di '.importo($importo, true)
            .' da '.$richiesta->portafoglioDaTesto().' a '.$richiesta->portafoglioATesto().'.</p>'
            .'<p><strong>Motivo:</strong> '.$richiesta->descrizione.'</p>'
            .'<p><a href="'.action([self::class, 'index']).'">Apri gestione portafoglio</a></p>';

        Notifica::notificaAdAdmin('Richiesta spostamento plafond', $testo, 'warning');

        return redirect()->action([DashboardController::class, 'show'])
            ->with('status', 'Richiesta inviata ad admin.');
    }

    public function sposta(Request $request)
    {
        abort_unless(Auth::user()?->hasPermissionTo('admin'), 403);

        $wallets = array_map(static fn (TipiPortafoglioEnum $case) => $case->value, TipiPortafoglioEnum::cases());

        $request->validate([
            'agente_id' => ['required', 'exists:users,id'],
            'portafoglio_da' => ['required', Rule::in($wallets)],
            'portafoglio_a' => ['required', Rule::in($wallets), 'different:portafoglio_da'],
            'importo' => ['required'],
            'descrizione' => ['required', 'max:255'],
        ], [
            'portafoglio_a.different' => 'Seleziona due portafogli diversi.',
        ]);

        $importo = (float) getInputNumero($request->input('importo'));

        if ($importo <= 0) {
            throw ValidationException::withMessages([
                'importo' => 'Inserisci un importo maggiore di zero.',
            ]);
        }

        DB::transaction(function () use ($request, $importo) {
            $agente = Agente::where('user_id', $request->input('agente_id'))->lockForUpdate()->first();

            if (! $agente) {
                throw ValidationException::withMessages([
                    'agente_id' => 'Agente non trovato.',
                ]);
            }

            $saldoDisponibile = $this->saldoPortafoglio($agente, $request->input('portafoglio_da'));

            if ($saldoDisponibile < $importo) {
                throw ValidationException::withMessages([
                    'importo' => 'Saldo insufficiente nel portafoglio di partenza.',
                ]);
            }

            $descrizione = trim((string) $request->input('descrizione'));
            $da = TipiPortafoglioEnum::from($request->input('portafoglio_da'))->testo();
            $a = TipiPortafoglioEnum::from($request->input('portafoglio_a'))->testo();

            $storno = new MovimentoPortafoglio;
            $storno->agente_id = $request->input('agente_id');
            $storno->portafoglio = $request->input('portafoglio_da');
            $storno->importo = -$importo;
            $storno->descrizione = "Storno admin da {$da} verso {$a}: {$descrizione}";
            $storno->save();

            $accredito = new MovimentoPortafoglio;
            $accredito->agente_id = $request->input('agente_id');
            $accredito->portafoglio = $request->input('portafoglio_a');
            $accredito->importo = $importo;
            $accredito->descrizione = "Accredito admin da {$da} verso {$a}: {$descrizione}";
            $accredito->save();
        });

        return redirect()->action([self::class, 'index'])->with('status', 'Portafoglio spostato correttamente.');
    }

    public function storna(Request $request)
    {
        abort_unless(Auth::user()?->hasPermissionTo('admin'), 403);

        $wallets = array_map(static fn (TipiPortafoglioEnum $case) => $case->value, TipiPortafoglioEnum::cases());

        $request->validate([
            'storno_agente_id' => ['required', 'exists:users,id'],
            'storno_portafoglio' => ['required', Rule::in($wallets)],
            'storno_importo' => ['required'],
            'storno_descrizione' => ['required', 'max:255'],
        ], [
            'storno_agente_id.required' => 'Seleziona un agente.',
            'storno_portafoglio.required' => 'Seleziona il portafoglio da stornare.',
            'storno_descrizione.required' => 'Inserisci il motivo dello storno.',
        ]);

        $importo = (float) getInputNumero($request->input('storno_importo'));

        if ($importo <= 0) {
            throw ValidationException::withMessages([
                'storno_importo' => 'Inserisci un importo maggiore di zero.',
            ]);
        }

        DB::transaction(function () use ($request, $importo) {
            $agente = Agente::where('user_id', $request->input('storno_agente_id'))->lockForUpdate()->first();

            if (! $agente) {
                throw ValidationException::withMessages([
                    'storno_agente_id' => 'Agente non trovato.',
                ]);
            }

            $portafoglio = TipiPortafoglioEnum::from($request->input('storno_portafoglio'))->testo();
            $descrizione = trim((string) $request->input('storno_descrizione'));

            $storno = new MovimentoPortafoglio;
            $storno->agente_id = $request->input('storno_agente_id');
            $storno->portafoglio = $request->input('storno_portafoglio');
            $storno->importo = -$importo;
            $storno->descrizione = "Storno admin su {$portafoglio}: {$descrizione}";
            $storno->save();
        });

        return redirect()->action([self::class, 'index'])->with('status', 'Storno portafoglio registrato correttamente.');
    }

    public function applicaRichiestaSpostamento(Request $request, int $id)
    {
        /** @var User $authUser */
        $authUser = Auth::user();
        abort_unless($authUser?->hasPermissionTo('admin'), 403);

        $richiesta = RichiestaSpostamentoPortafoglio::with('agente')->find($id);
        abort_if(! $richiesta, 404, 'Richiesta non trovata');

        DB::transaction(function () use ($richiesta, $authUser) {
            $richiesta = RichiestaSpostamentoPortafoglio::whereKey($richiesta->id)->lockForUpdate()->first();

            if (! $richiesta || $richiesta->stato !== 'in_attesa') {
                throw ValidationException::withMessages([
                    'richiesta' => 'Questa richiesta è già stata gestita.',
                ]);
            }

            $agente = Agente::where('user_id', $richiesta->agente_id)->lockForUpdate()->first();

            if (! $agente) {
                throw ValidationException::withMessages([
                    'richiesta' => 'Profilo agente non trovato.',
                ]);
            }

            if ($this->saldoPortafoglio($agente, $richiesta->portafoglio_da) < (float) $richiesta->importo) {
                throw ValidationException::withMessages([
                    'richiesta' => 'Saldo insufficiente nel portafoglio di partenza.',
                ]);
            }

            $this->creaMovimentiSpostamento(
                $richiesta->agente_id,
                $richiesta->portafoglio_da,
                $richiesta->portafoglio_a,
                (float) $richiesta->importo,
                $richiesta->descrizione,
                'Richiesta agente'
            );

            $richiesta->admin_id = $authUser->id;
            $richiesta->stato = 'applicata';
            $richiesta->applicata_il = now();
            $richiesta->save();
        });

        $richiesta->refresh();
        if ($richiesta->agente) {
            $testo = '<p>Admin ha applicato lo spostamento di '.importo((float) $richiesta->importo, true)
                .' da '.$richiesta->portafoglioDaTesto().' a '.$richiesta->portafoglioATesto().'.</p>'
                .'<p><strong>Motivo:</strong> '.$richiesta->descrizione.'</p>';

            Notifica::notificaAdAgente($richiesta->agente, 'Spostamento plafond applicato', $testo, 'success');
        }

        return redirect()->action([self::class, 'index'])
            ->with('status', 'Spostamento applicato e agente notificato.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return mixed
     */
    public function show($id)
    {
        $record = MovimentoPortafoglio::find($id);
        abort_if(! $record, 404, 'Questo portafoglio non esiste');

        return view('Backend.Portafoglio.show', [
            'record' => $record,
            'controller' => PortafoglioController::class,
            'titoloPagina' => MovimentoPortafoglio::NOME_SINGOLARE,
            'breadcrumbs' => [action([PortafoglioController::class, 'index']) => 'Torna a elenco '.MovimentoPortafoglio::NOME_PLURALE],

        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return mixed
     */
    public function edit($id)
    {
        $record = MovimentoPortafoglio::find($id);
        abort_if(! $record, 404, 'Questo portafoglio non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }

        return view('Backend.Portafoglio.edit', [
            'record' => $record,
            'controller' => PortafoglioController::class,
            'titoloPagina' => 'Modifica '.MovimentoPortafoglio::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([PortafoglioController::class, 'index']) => 'Torna a elenco '.MovimentoPortafoglio::NOME_PLURALE],

        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return mixed
     */
    public function update(Request $request, $id)
    {
        $record = MovimentoPortafoglio::find($id);
        abort_if(! $record, 404, 'Questo '.MovimentoPortafoglio::NOME_SINGOLARE.' non esiste');
        $request->validate($this->rules($id));
        $this->salvaDati($record, $request);

        return $this->backToIndex();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return mixed
     */
    public function destroy($id)
    {
        $record = MovimentoPortafoglio::find($id);
        abort_if(! $record, 404, 'Questo portafoglio non esiste');

        $record->delete();

        return [
            'success' => true,
            'redirect' => action([PortafoglioController::class, 'index']),
        ];
    }

    /**
     * @param  MovimentoPortafoglio  $model
     * @param  Request  $request
     * @return mixed
     */
    protected function salvaDati($model, $request)
    {

        $nuovo = ! $model->id;

        if ($nuovo) {

        }

        // Ciclo su campi
        $campi = [
            'agente_id' => '',
            'portafoglio' => '',
            'importo' => 'app\getInputNumero',
            'descrizione' => '',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $model->save();

        return $model;
    }

    protected function backToIndex()
    {
        return redirect()->action([get_class($this), 'index']);
    }

    /** Query per index
     * @return array
     */
    protected function queryBuilderIndexSemplice()
    {
        return MovimentoPortafoglio::get();
    }

    protected function rules($id = null)
    {

        $rules = [
            'agente_id' => ['required'],
            'portafoglio' => ['required', Rule::in(array_map(static fn (TipiPortafoglioEnum $case) => $case->value, TipiPortafoglioEnum::cases()))],
            'importo' => ['required'],
            'descrizione' => ['required', 'max:255'],
        ];

        return $rules;
    }

    protected function creaMovimentiSpostamento(
        int $agenteId,
        string $portafoglioDa,
        string $portafoglioA,
        float $importo,
        string $descrizione,
        string $prefisso = 'Spostamento admin'
    ): void {
        $da = TipiPortafoglioEnum::from($portafoglioDa)->testo();
        $a = TipiPortafoglioEnum::from($portafoglioA)->testo();

        $storno = new MovimentoPortafoglio;
        $storno->agente_id = $agenteId;
        $storno->portafoglio = $portafoglioDa;
        $storno->importo = -$importo;
        $storno->descrizione = "{$prefisso} da {$da} verso {$a}: {$descrizione}";
        $storno->save();

        $accredito = new MovimentoPortafoglio;
        $accredito->agente_id = $agenteId;
        $accredito->portafoglio = $portafoglioA;
        $accredito->importo = $importo;
        $accredito->descrizione = "{$prefisso} da {$da} verso {$a}: {$descrizione}";
        $accredito->save();
    }

    protected function saldoPortafoglio(Agente $agente, string $portafoglio): float
    {
        return match ($portafoglio) {
            TipiPortafoglioEnum::SERVIZI->value => (float) $agente->portafoglio_servizi,
            TipiPortafoglioEnum::SPEDIZIONI->value => (float) $agente->portafoglio_spedizioni,
            TipiPortafoglioEnum::VISURE->value => (float) ($agente->portafoglio_visure ?? 0),
            default => 0,
        };
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
}
