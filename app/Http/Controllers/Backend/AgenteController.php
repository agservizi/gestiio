<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\MieClassi\AlertMessage;
use App\Http\Services\LockerAgentSubscriptionService;
use App\Http\Services\LuggageAgentSubscriptionService;
use App\Models\Agente;
use App\Models\Gestore;
use App\Models\Mandato;
use App\Models\MovimentoPortafoglio;
use App\Models\ProduzioneOperatore;
use App\Models\RegistroLogin;
use App\Models\Ticket;
use App\Models\TipoContratto;
use App\Models\User;
use App\Policies\LockerPackagePolicy;
use App\Policies\LuggageDepositPolicy;
use App\Notifications\DatiAccessoNotification;
use App\Notifications\PasswordResetNotification;
use App\Rules\CodiceFiscaleRule;
use App\Rules\IbanRule;
use App\Rules\PartitaIvaRule;
use App\Rules\PasswordRules;
use App\Rules\TelefonoRule;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

class AgenteController extends Controller
{
    protected $conFiltro = false;

    /**
     * Raggruppa i permessi granulari di Registro/Impostazioni/Controlli Contratti/SEND
     * in pochi toggle "di comodo": abilitando un gruppo si abilitano tutti i
     * permessi che contiene.
     */
    public const GRUPPI_PERMESSI_AVANZATI = [
        'gruppo:registro-visualizza' => [
            'registro.login.view',
            'registro.email.view',
            'registro.backup.view',
            'registro.licenze.view',
            'registro.info-sito.view',
            'registro.errori.view',
            'registro.upload.view',
            'registro.modifiche.view',
        ],
        'gruppo:registro-backup' => [
            'registro.backup.run',
            'registro.backup.download',
        ],
        'gruppo:settings-visualizza' => [
            'settings.view',
            'settings.test',
        ],
        'gruppo:settings-avanzate' => [
            'settings.export',
            'settings.import',
            'settings.rollback',
        ],
        'gruppo:controlli-contratti' => [
            'controlli-contratti.view',
            'controlli-contratti.update',
        ],
        'gruppo:send-operatore' => [
            'send.access',
            'send.requests.view',
            'send.requests.view-own',
            'send.requests.create',
            'send.requests.update',
            'send.requests.submit',
            'send.requests.cancel',
            'send.documents.view',
            'send.documents.upload',
            'send.documents.download',
        ],
        'gruppo:send-supervisore' => [
            'send.requests.view-all',
            'send.requests.assign',
            'send.requests.take-charge',
            'send.requests.request-integration',
            'send.requests.process',
            'send.requests.complete',
            'send.requests.reject',
            'send.documents.delete',
            'send.notes.view-internal',
            'send.notes.create-internal',
        ],
        'gruppo:send-admin' => [
            'send.requests.delete',
            'send.requests.reopen',
            'send.audit.view',
            'send.reports.view',
            'send.settings.manage',
        ],
    ];

    public const ETICHETTE_GRUPPI_AVANZATI = [
        'gruppo:registro-visualizza' => 'Registro: Visualizzazione',
        'gruppo:registro-backup' => 'Registro: Gestione Backup',
        'gruppo:settings-visualizza' => 'Impostazioni: Visualizzazione',
        'settings.update' => 'Impostazioni: Modifica',
        'gruppo:settings-avanzate' => 'Impostazioni: Import / Export / Rollback',
        'gruppo:controlli-contratti' => 'Controlli Contratti',
        'gruppo:send-operatore' => 'SEND: Operatore sportello',
        'gruppo:send-supervisore' => 'SEND: Supervisore / lavorazione',
        'gruppo:send-admin' => 'SEND: Audit e impostazioni',
    ];

    /**
     * Display a listing of the resource.
     *
     * @return mixed
     */
    public function index(Request $request)
    {
        $nomeClasse = get_class($this);
        $recordsQB = $this->applicaFiltri($request);

        $ordinamenti = [
            'recente' => ['testo' => 'Più recente', 'filtro' => function ($q) {
                return $q->orderBy('id', 'desc');
            }],

            'nominativo' => ['testo' => 'Nominativo', 'filtro' => function ($q) {
                return $q->orderBy('cognome')->orderBy('name');
            }],

        ];

        /** @var User|null $authUser */
        $authUser = Auth::user();
        $orderByUser = $authUser?->getExtra($nomeClasse);
        $orderByString = $request->input('orderBy');

        if ($orderByString) {
            $orderBy = $orderByString;
        } elseif ($orderByUser) {
            $orderBy = $orderByUser;
        } else {
            $orderBy = 'recente';
        }

        if ($authUser instanceof User && $orderByUser != $orderByString) {
            $authUser->setExtra([$nomeClasse => $orderBy]);
        }

        // Applico ordinamento
        $recordsQB = call_user_func($ordinamenti[$orderBy]['filtro'], $recordsQB);

        $totali = [
            'totale' => (clone $recordsQB)->count(),
            'attivi' => (clone $recordsQB)->whereHas('permissions')->count(),
            'sospesi' => (clone $recordsQB)->whereDoesntHave('permissions')->count(),
            'con2fa' => (clone $recordsQB)->whereNotNull('two_factor_secret')->count(),
        ];

        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        if ($request->ajax()) {

            return [
                'html' => base64_encode(view('Backend.Agente.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ])->render()),
                'kpi' => base64_encode(view('Backend.Agente._kpi', [
                    'totali' => $totali,
                ])->render()),
            ];

        }

        return view('Backend.Agente.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco '.Agente::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo '.Agente::NOME_SINGOLARE,
            'testoCerca' => 'cerca in nominativo, email, telefono',
            'totali' => $totali,

        ]);

    }

    /**
     * @param  Request  $request
     * @return Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = User::where('id', '>', 1)
            ->with('permissions')
            ->with('agente')
            ->whereHas('agente');

        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',alias,nome,cognome,email,telefono)'), 'like', "%$t%");
            }
            $this->conFiltro = true;
        }

        $ruolo = $request->input('ruolo');
        if ($ruolo) {
            $queryBuilder->whereHas('permissions', function ($query) use ($ruolo) {
                $query->where('name', $ruolo);
            });
            $this->conFiltro = true;
        }

        $statoUtenza = $request->input('stato_utenza');
        if ($statoUtenza === 'attivo') {
            $queryBuilder->whereHas('permissions');
            $this->conFiltro = true;
        } elseif ($statoUtenza === 'sospeso') {
            $queryBuilder->whereDoesntHave('permissions');
            $this->conFiltro = true;
        }

        $filtro2fa = $request->input('filtro_2fa');
        if ($filtro2fa === 'attivo') {
            $queryBuilder->whereNotNull('two_factor_secret');
            $this->conFiltro = true;
        } elseif ($filtro2fa === 'non_attivo') {
            $queryBuilder->whereNull('two_factor_secret');
            $this->conFiltro = true;
        }

        $ultimoAccesso = $request->input('ultimo_accesso');
        if ($ultimoAccesso === '7gg') {
            $queryBuilder->where('ultimo_accesso', '>=', now()->subDays(7));
            $this->conFiltro = true;
        } elseif ($ultimoAccesso === '30gg') {
            $queryBuilder->where('ultimo_accesso', '>=', now()->subDays(30));
            $this->conFiltro = true;
        } elseif ($ultimoAccesso === 'mai') {
            $queryBuilder->whereNull('ultimo_accesso');
            $this->conFiltro = true;
        }

        return $queryBuilder;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return mixed
     */
    public function create()
    {
        $record = new User;

        return view('Backend.Agente.edit', array_merge([
            'record' => $record,
            'titoloPagina' => 'Nuovo '.Agente::NOME_SINGOLARE,
            'controller' => get_class($this),
            'ruoli' => $this->ruoliApplicabili(),
            'newPassword' => rand(111111, 999999),
            'breadcrumbs' => [action([AgenteController::class, 'index']) => 'Torna a elenco '.Agente::NOME_PLURALE],

        ], $this->datiPermessiAvanzati($record)));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return mixed
     */
    public function store(Request $request)
    {
        $request->validate($this->rules(null));
        $record = new User;
        $this->salvaDatiUtente($record, $request);
        $this->salvadatiAgente(new Agente, $request, $record);
        $this->finalizeLuggageAgentAccess($record, $request);
        $this->finalizeLockerAgentAccess($record, $request);

        return $this->backToIndex();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return mixed
     */
    public function show($id)
    {
        $record = User::where('id', '>', 1)->find($id);
        abort_if(! $record, 404, 'Questo operatore non esiste');
        $controller = get_class($this);

        $questoMese = now();
        $mesePrecedente = $questoMese->copy()->subMonths(1);

        $ticketTotali = Ticket::where('agente_id', $id)->count();
        $ticketAperti = Ticket::where('agente_id', $id)->where('stato', '<>', 'chiuso')->count();
        $ticketChiusi = Ticket::where('agente_id', $id)->where('stato', 'chiuso')->count();
        $loginUltimi30 = RegistroLogin::where('user_id', $id)->where('riuscito', 1)->where('created_at', '>=', now()->subDays(30))->count();

        return view('Backend.Agente.show', [
            'record' => $record,
            'titoloPagina' => $record->nominativo(),
            'controller' => $controller,
            'breadcrumbs' => [action([$controller, 'index']) => 'Torna a elenco '.Agente::NOME_PLURALE],
            'records' => $this->queryProduzione($id),
            'produzioneMese' => ProduzioneOperatore::findByIdAnnoMese($id, $questoMese->year, $questoMese->month),
            'produzioneMesePrecedente' => ProduzioneOperatore::findByIdAnnoMese($id, $mesePrecedente->year, $mesePrecedente->month),
            'kpiAgente' => [
                'ticket_totali' => $ticketTotali,
                'ticket_aperti' => $ticketAperti,
                'ticket_chiusi' => $ticketChiusi,
                'login_30gg' => $loginUltimi30,
                'portafoglio_servizi' => (float) ($record->agente?->portafoglio_servizi ?? 0),
                'portafoglio_spedizioni' => (float) ($record->agente?->portafoglio_spedizioni ?? 0),
            ],
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
        abort_if($id == 1, 404);
        $record = User::withCount(['contratti', 'cafPatronato'])->find($id);
        abort_if(! $record, 404, 'Questo agente non esiste');
        if (($record->contratti_count + $record->caf_patronato_count) > 0) {
            $eliminabile = 'Non eliminabile';
        } else {
            $eliminabile = true;

        }

        /** @var User|null $authUser */
        $authUser = Auth::user();
        if ($record->can('admin') && ! ($authUser?->can('admin') ?? false)) {
            abort(403, 'Non hai il permesso per effettuare questa operazione');
        }

        return view('Backend.Agente.edit', array_merge([
            'record' => $record,
            'controller' => AgenteController::class,
            'titoloPagina' => 'Modifica '.Agente::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([AgenteController::class, 'index']) => 'Torna a elenco '.Agente::NOME_PLURALE],
            'ruoli' => $this->ruoliApplicabili(),
        ], $this->datiPermessiAvanzati($record)));
    }

    /**
     * Calcola l'elenco dei permessi da escludere dal blocco "Servizi" (perché mostrati
     * come gruppi nel blocco "Registro & Impostazioni") e lo stato checked di ogni gruppo.
     */
    protected function datiPermessiAvanzati(User $record): array
    {
        $permessiAvanzati = array_merge(...array_values(self::GRUPPI_PERMESSI_AVANZATI));
        $permessiAvanzati[] = 'settings.update';

        // Nascondi eventuali send.* residui non mappati nei gruppi.
        $sendResidui = Permission::query()
            ->where('name', 'like', 'send.%')
            ->pluck('name')
            ->all();
        $permessiAvanzati = array_values(array_unique(array_merge($permessiAvanzati, $sendResidui)));

        $gruppiAvanzati = [];
        foreach (self::ETICHETTE_GRUPPI_AVANZATI as $chiave => $etichetta) {
            $permessiGruppo = self::GRUPPI_PERMESSI_AVANZATI[$chiave] ?? [$chiave];
            $gruppiAvanzati[$chiave] = [
                'etichetta' => $etichetta,
                'checked' => $record->id && collect($permessiGruppo)->every(fn ($p) => $record->hasPermissionTo($p)),
            ];
        }

        return [
            'permessiAvanzati' => $permessiAvanzati,
            'gruppiAvanzati' => $gruppiAvanzati,
        ];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return mixed
     */
    public function update(Request $request, $id)
    {
        $record = User::find($id);
        abort_if(! $record, 404, 'Questo '.Agente::NOME_SINGOLARE.' non esiste');
        $request->validate($this->rules($id));
        $this->salvaDatiUtente($record, $request);
        $this->salvadatiAgente(Agente::firstOrNew(['user_id' => $id]), $request, $record);
        $this->finalizeLuggageAgentAccess($record, $request);
        $this->finalizeLockerAgentAccess($record, $request);

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

        $agente = Agente::firstWhere('user_id', $id);
        if ($agente->visura_camerale) {
            Storage::delete($agente->visura_camerale);
        }
        $agente->delete();

        $record = User::find($id);
        abort_if(! $record, 404, 'Questo agente non esiste');
        $record->delete();

        return [
            'success' => true,
            'redirect' => action([AgenteController::class, 'index']),
        ];
    }

    public function azioni(Request $request, $id, $azione)
    {
        $u = User::find($id);
        if (! $u) {
            return ['success' => false, 'message' => 'Questo utente non esiste'];
        }
        switch ($azione) {

            case 'sospendi':
                $u->syncPermissions([]);

                return ['success' => true, 'redirect' => action([AgenteController::class, 'index'])];

            case 'impersona':
                return $this->azioneImpersona($id);

            case 'invia-mail-password-reset':
                return $this->azioneInviaMailPasswordReset($id);

            case 'resetta-password':
                return $this->azioneResettaPassword($id);

            case 'imposta_mandato':
                $mandatoId = $request->input('mandato');
                $stato = $request->input('stato');
                $record = Mandato::where('agente_id', $id)->where('gestore_id', $mandatoId)->first();
                if (! $record) {
                    $record = new Mandato;
                    $record->agente_id = $id;
                    $record->gestore_id = $mandatoId;
                }
                $record->attivo = $stato;
                $record->save();

                return ['success' => true, 'mandatoId' => $mandatoId, 'stato' => $stato];

            case 'ricalcola_provvigioni':

                $produzioneOperatore = ProduzioneOperatore::findByIdAnnoMese($id, $request->input('anno'), $request->input('mese'));
                if ($produzioneOperatore) {
                    $produzioneOperatore->ricalcola();

                    return ['success' => true, 'title' => 'Provvigioni ricalcolate', 'message' => 'La provvigione è stata ricalcolata'];

                } else {
                    return ['success' => true, 'title' => 'Provvigioni non trovata', 'message' => 'La provvigione non è stata trovata'];

                }

            default:
                return ['success' => false, 'title' => 'Azione sconosciouta', 'message' => 'Azione '.$azione.' non prevista'];

        }

    }

    public function tab($id, $tab)
    {
        $cliente = User::find($id);

        abort_if(! $cliente, 404);
        switch ($tab) {
            case 'tab_mandati':
                return view('Backend.Agente.show.tabMandati', [
                    'gestori' => Gestore::get(),
                    'mandati' => Mandato::where('agente_id', $id)->get(),
                    'controller' => AgenteController::class,
                    'id' => $id,
                ]);

            case 'tab_provvigioni':
                return view('Backend.Agente.show.tabProvvigioni', [
                    'gestori' => TipoContratto::with('gestore')->get(),
                    'mandati' => Mandato::where('agente_id', $id)->get(),
                    'controller' => AgenteController::class,
                    'id' => $id,
                ]);

            case 'tab_produzione':
                return view('Backend.Agente.show.tabProduzione', [
                    'records' => $this->queryProduzione($id),
                    'id' => $id,
                    'controller' => AgenteController::class,

                ]);

            case 'tab_login':
                $records = RegistroLogin::with('utente')->with('impersonatoDa')->where('user_id', $id)->latest()->paginate();

                return view('Backend.Agente.show.tabLogin', [
                    'records' => $records,
                    'id' => $id,
                ]);

            case 'tab_portafoglio':
                return view('Backend.Agente.show.tabPortafoglio', [
                    'records' => MovimentoPortafoglio::withoutGlobalScope('filtroOperatore')->where('agente_id', $id)->latest()->paginate(),
                    'id' => $id,
                    'controller' => AgenteController::class,
                ]);

            default:

                return "il tab  $tab non esiste";
        }
    }

    /**
     * @param  User  $model
     * @param  Request  $request
     * @return mixed
     */
    protected function salvaDatiUtente($model, $request)
    {

        $nuovo = ! $model->id;

        if ($nuovo) {
            $model->password = Hash::make(Str::uuid());
        }

        // Ciclo su campi
        $campi = [
            'nome' => 'app\getInputUcwords',
            'cognome' => 'app\getInputUcwords',
            'email' => 'strtolower',
            'telefono' => 'app\getInputTelefono',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $password = $request->input('password');
        if ($password) {
            $model->password = Hash::make($password);
        }
        $model->save();

        $permessiEspansi = $this->expandVediPermissions((array) $request->input('vedi', []));
        $permessiEspansi = array_values(array_filter(
            $permessiEspansi,
            fn (string $permesso) => $permesso !== LuggageDepositPolicy::PERMISSION
        ));
        $model->syncPermissions(array_merge([$request->input('ruolo')], $permessiEspansi));

        if ($nuovo) {
            dispatch(function () use ($model, $password) {
                $model->notify(new DatiAccessoNotification($password));

            })->afterResponse();

        }

        return $model;
    }

    protected function expandVediPermissions(array $vedi): array
    {
        $permessiEspansi = [];

        foreach ($vedi as $voce) {
            if (array_key_exists($voce, self::GRUPPI_PERMESSI_AVANZATI)) {
                array_push($permessiEspansi, ...self::GRUPPI_PERMESSI_AVANZATI[$voce]);
                if (str_starts_with($voce, 'gruppo:send-')) {
                    $permessiEspansi[] = 'servizio_send';
                }
            } elseif ($voce === 'servizio_send') {
                // Toggle Servizi: attiva il modulo + pacchetto operatore.
                $permessiEspansi[] = 'servizio_send';
                array_push($permessiEspansi, ...self::GRUPPI_PERMESSI_AVANZATI['gruppo:send-operatore']);
            } else {
                $permessiEspansi[] = $voce;
            }
        }

        return array_values(array_unique($permessiEspansi));
    }

    protected function finalizeLuggageAgentAccess(User $user, Request $request): void
    {
        if ($user->hasPermissionTo('admin')) {
            return;
        }

        $permessi = $this->expandVediPermissions((array) $request->input('vedi', []));
        $wantsLuggage = in_array(LuggageDepositPolicy::PERMISSION, $permessi, true);
        $service = app(LuggageAgentSubscriptionService::class);

        if (! $wantsLuggage) {
            $service->revokeAccess($user);

            return;
        }

        if ($service->ensureAccessGranted($user)) {
            return;
        }

        $service->revokeAccess($user);
        (new AlertMessage)->messaggio(
            'Deposito Bagagli non attivato: portafoglio servizi insufficiente per il canone mensile di '.importo($service->monthlyFee(), true).'.',
            'warning'
        )->flash();
    }

    protected function finalizeLockerAgentAccess(User $user, Request $request): void
    {
        if ($user->hasPermissionTo('admin')) {
            return;
        }

        $permessi = $this->expandVediPermissions((array) $request->input('vedi', []));
        $wantsLocker = in_array(LockerPackagePolicy::PERMISSION, $permessi, true);
        $service = app(LockerAgentSubscriptionService::class);

        if (! $wantsLocker) {
            $service->revokeAccess($user);

            return;
        }

        if ($service->ensureAccessGranted($user)) {
            return;
        }

        $service->revokeAccess($user);
        (new AlertMessage)->messaggio(
            'Locker Point non attivato: portafoglio servizi insufficiente per il canone mensile di '.importo($service->monthlyFee(), true).'.',
            'warning'
        )->flash();
    }

    /**
     * @param  Agente  $model
     * @param  Request  $request
     * @param  User  $user
     * @return mixed
     */
    protected function salvadatiAgente($model, $request, $user)
    {

        $nuovo = ! $model->id;

        if ($nuovo) {
            $model->user_id = $user->id;
        }

        // Ciclo su campi
        $campi = [
            'ragione_sociale' => 'app\getInputUcwords',
            'codice_fiscale' => 'strtoupper',
            'partita_iva' => '',
            'indirizzo' => '',
            'cap' => '',
            'citta' => '',
            'iban' => 'strtoupper',
            'listino_telefonia_id' => '',
            'paga_con_paypal' => 'app\getInputCheckbox',
            'openapi_visure_token' => '',
            'openapi_catasto_token' => '',
            'openapi_email' => '',
            'openapi_api_key' => '',

        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        /** @var User|null $authUser */
        $authUser = Auth::user();
        if ($authUser && $authUser->hasPermissionTo('admin')) {
            $model->openapi_visure_token = trim((string) $model->openapi_visure_token) ?: null;
            $model->openapi_catasto_token = trim((string) $model->openapi_catasto_token) ?: null;
            $model->openapi_email = trim((string) $model->openapi_email) ?: null;
            $model->openapi_api_key = trim((string) $model->openapi_api_key) ?: null;
        } else {
            unset(
                $model->openapi_visure_token,
                $model->openapi_catasto_token,
                $model->openapi_email,
                $model->openapi_api_key
            );
        }
        $model->save();

        $user->alias = $model->ragione_sociale ?: ($user->cognome.' '.$user->nome);
        $user->save();

        if ($request->file('visura_camerale')) {

            $tmpFile = $request->file('visura_camerale');
            $extensione = $tmpFile->getClientOriginalExtension();
            $filename = hexdec(uniqid()).'.'.$extensione;
            $cartella = config('configurazione.visure_camerali.cartella');
            $tmpFile->storeAs($cartella, $filename);
            $model->visura_camerale = $cartella.'/'.$filename;
            $model->save();

        }

        if ($model->wasChanged('listino_telefonia_id')) {
            ProduzioneOperatore::calcolaTotaliOrdiniInPagamento($model->user_id, now()->year, now()->month);
        }

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
        return Agente::get();
    }

    protected function rules($id = null)
    {

        $rules = [
            'nome' => ['required', 'max:255'],
            'cognome' => ['required', 'max:255'],
            'codice_fiscale' => ['nullable', new CodiceFiscaleRule],
            'partita_iva' => ['nullable', new PartitaIvaRule],
            'iban' => ['nullable', new IbanRule],
            'email' => [
                'nullable',
                'required_without:telefono',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($id),

            ],
            'telefono' => [
                'nullable',
                'required_without:email',
                'max:255',
                new TelefonoRule,
                Rule::unique(User::class)->ignore($id),
            ],
            'password' => [$id == null ? 'required' : 'nullable', 'string', new PasswordRules],
            'openapi_visure_token' => ['nullable', 'string', 'max:2048'],
            'openapi_catasto_token' => ['nullable', 'string', 'max:2048'],
            'openapi_email' => ['nullable', 'string', 'email', 'max:255'],
            'openapi_api_key' => ['nullable', 'string', 'max:2048'],

        ];

        return $rules;
    }

    protected function ruoliApplicabili()
    {
        return Permission::where('id', '<=', 3)->orWhere('name', 'operatore')->orderBy('name')->get()->pluck('name')->toArray();
    }

    protected function azioneImpersona($id)
    {

        $user = User::find($id);
        if ($user->hasPermissionTo('admin') && Auth::id() != 1) {
            return ['success' => false, 'message' => 'Non puoi impersonare questo utente'];
        }

        Session::put('impersona', Auth::id());
        Auth::loginUsingId($id, false);

        return ['success' => true, 'redirect' => '/'];
    }

    protected function azioneInviaMailPasswordReset($id)
    {
        $user = User::find($id);

        if (! $user || ! $user->email) {
            Log::warning('AgenteController: invio reset password non eseguito, email non valida', [
                'azione' => 'invia-mail-password-reset',
                'agente_id' => $id,
                'utente_trovato' => (bool) $user,
            ]);

            return ['success' => false, 'title' => 'Email non valida', 'message' => 'L\'agente non ha un indirizzo email valido.'];
        }

        try {
            /** @var PasswordBroker $passwordBroker */
            $passwordBroker = Password::broker('new_users');
            $token = $passwordBroker->createToken($user);
            $user->notify(new PasswordResetNotification($token));
            $sentAt = now()->format('d/m/Y H:i:s');
            Log::info('AgenteController: email reset password inviata', [
                'azione' => 'invia-mail-password-reset',
                'agente_id' => $user->id,
                'email' => $user->email,
                'sent_at' => $sentAt,
            ]);
        } catch (\Throwable $e) {
            report($e);
            Log::error('AgenteController: errore invio email reset password', [
                'azione' => 'invia-mail-password-reset',
                'agente_id' => $user->id,
                'email' => $user->email,
                'errore' => $e->getMessage(),
            ]);

            return ['success' => false, 'title' => 'Invio fallito', 'message' => $this->friendlyMailError($e)];
        }

        return [
            'success' => true,
            'title' => 'Email inviata',
            'message' => 'La mail con il link per impostare la password è stata inviata all\'indirizzo '.$user->email,
            'sent_at' => $sentAt,
        ];

    }

    protected function azioneResettaPassword($id)
    {
        $user = User::find($id);
        if (! $user || ! $user->email) {
            Log::warning('AgenteController: reset password non eseguito, email non valida', [
                'azione' => 'resetta-password',
                'agente_id' => $id,
                'utente_trovato' => (bool) $user,
            ]);

            return ['success' => false, 'title' => 'Email non valida', 'message' => 'L\'agente non ha un indirizzo email valido.'];
        }

        $nuovaPassword = '123456';
        $user->password = bcrypt($nuovaPassword);
        $user->save();

        try {
            $user->notify(new DatiAccessoNotification($nuovaPassword));
            $sentAt = now()->format('d/m/Y H:i:s');
            Log::info('AgenteController: password resettata e email inviata', [
                'azione' => 'resetta-password',
                'agente_id' => $user->id,
                'email' => $user->email,
                'sent_at' => $sentAt,
            ]);
        } catch (\Throwable $e) {
            report($e);
            Log::error('AgenteController: errore invio email dopo reset password', [
                'azione' => 'resetta-password',
                'agente_id' => $user->id,
                'email' => $user->email,
                'errore' => $e->getMessage(),
            ]);

            return ['success' => false, 'title' => 'Password impostata', 'message' => 'Password aggiornata ma invio email fallito: '.$this->friendlyMailError($e)];
        }

        return [
            'success' => true,
            'title' => 'Password impostata',
            'message' => 'La password è stata impostata a 123456 e inviata via email a '.$user->email,
            'sent_at' => $sentAt,
        ];
    }

    protected function queryProduzione($id)
    {
        return ProduzioneOperatore::where('user_id', $id)->orderByDesc('anno')->orderByDesc('mese')->paginate();
    }

    protected function friendlyMailError(\Throwable $e): string
    {
        $message = (string) $e->getMessage();
        $lower = strtolower($message);

        if (str_contains($lower, 'you can only send testing emails') || str_contains($lower, 'verify a domain at resend.com/domains')) {
            return 'Il provider Resend è in modalità test: puoi inviare solo verso l\'indirizzo autorizzato. Per inviare ad altri destinatari devi verificare un dominio su resend.com/domains e usare un mittente FROM su quel dominio.';
        }

        return 'Errore durante invio email: '.$message;
    }
}
