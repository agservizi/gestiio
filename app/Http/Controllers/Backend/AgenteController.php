<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Gestore;
use App\Models\Mandato;
use App\Models\MovimentoPortafoglio;
use App\Models\ProduzioneOperatore;
use App\Models\RegistroLogin;
use App\Models\TipoContratto;
use App\Models\User;
use App\Notifications\DatiAccessoNotification;
use App\Notifications\NuovoUtenteInfoNotification;
use App\Notifications\PasswordResetNotification;
use App\Rules\CodiceFiscaleRule;
use App\Rules\NumeroItRule;
use App\Rules\PartitaIvaRule;
use App\Rules\PasswordRules;
use App\Rules\TelefonoItalianoRule;
use App\Rules\TelefonoRule;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Models\Agente;
use DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\RecoveryCode;
use Spatie\Permission\Models\Permission;

class AgenteController extends Controller
{
    protected $conFiltro = false;


    /**
     * The two factor authentication provider.
     *
     * @var \Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider
     */
    protected $provider;

    /**
     * Create a new action instance.
     *
     * @param \Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider $provider
     * @return void
     */
    public function __construct(TwoFactorAuthenticationProvider $provider)
    {
        $this->provider = $provider;
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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
            }]

        ];

        $orderByUser = Auth::user()->getExtra($nomeClasse);
        $orderByString = $request->input('orderBy');

        if ($orderByString) {
            $orderBy = $orderByString;
        } else if ($orderByUser) {
            $orderBy = $orderByUser;
        } else {
            $orderBy = 'recente';
        }

        if ($orderByUser != $orderByString) {
            Auth::user()->setExtra([$nomeClasse => $orderBy]);
        }

        //Applico ordinamento
        $recordsQB = call_user_func($ordinamenti[$orderBy]['filtro'], $recordsQB);

        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        if ($request->ajax()) {

            return [
                'html' => base64_encode(view('Backend.Agente.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.Agente.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\Agente::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\Agente::NOME_SINGOLARE,
            'testoCerca' => 'cerca in nominativo, email, telefono'

        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = User::where('id', '>', 1)
            ->with('permissions')
            ->with('agente')
            ->has('permissions');
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',alias,nome,cognome,email,telefono)'), 'like', "%$t%");
            }
        }

        //$this->conFiltro = true;
        return $queryBuilder;
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $record = new User();

        return view('Backend.Agente.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . Agente::NOME_SINGOLARE,
            'controller' => get_class($this),
            'ruoli' => $this->ruoliApplicabili(),
            'newPassword' => rand(111111, 999999),
            'breadcrumbs' => [action([AgenteController::class, 'index']) => 'Torna a elenco ' . Agente::NOME_PLURALE],


        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate($this->rules(null));
        $record = new User();
        $this->salvaDatiUtente($record, $request);
        $this->salvadatiAgente(new Agente(), $request, $record);

        /*
        $record->forceFill([
            'two_factor_secret' => encrypt($this->provider->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode(Collection::times(8, function () {
                return RecoveryCode::generate();
            })->all())),
        ])->save();
        */

        return $this->backToIndex();
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $record = User::where('id', '>', 1)->find($id);
        abort_if(!$record, 404, 'Questo operatore non esiste');
        $controller = get_class($this);

        $questoMese = now();
        $mesePrecedente = $questoMese->copy()->subMonths(1);
        return view('Backend.Agente.show', [
            'record' => $record,
            'titoloPagina' => $record->nominativo(),
            'controller' => $controller,
            'breadcrumbs' => [action([$controller, 'index']) => 'Torna a elenco ' . \App\Models\Agente::NOME_PLURALE],
            'records' => $this->queryProduzione($id),
            'produzioneMese' => ProduzioneOperatore::findByIdAnnoMese($id, $questoMese->year, $questoMese->month),
            'produzioneMesePrecedente' => ProduzioneOperatore::findByIdAnnoMese($id, $mesePrecedente->year, $mesePrecedente->month),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        abort_if($id == 1, 404);
        $record = User::withCount(['contratti', 'cafPatronato'])->find($id);
        abort_if(!$record, 404, 'Questo agente non esiste');
        if (($record->contratti_count + $record->caf_patronato_count) > 0) {
            $eliminabile = 'Non eliminabile';
        } else {
            $eliminabile = true;

        }

        if ($record->hasPermissionTo('admin') && !Auth::user()->hasPermissionTo('admin')) {
            abort(403, 'Non hai il permesso per effettuare questa operazione');
        }


        return view('Backend.Agente.edit', [
            'record' => $record,
            'controller' => AgenteController::class,
            'titoloPagina' => 'Modifica ' . Agente::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([AgenteController::class, 'index']) => 'Torna a elenco ' . Agente::NOME_PLURALE],
            'ruoli' => $this->ruoliApplicabili()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $record = User::find($id);
        abort_if(!$record, 404, 'Questo ' . Agente::NOME_SINGOLARE . ' non esiste');
        $request->validate($this->rules($id));
        $this->salvaDatiUtente($record, $request);
        $this->salvadatiAgente(Agente::firstOrNew(['user_id' => $id]), $request, $record);


        return $this->backToIndex();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

        $agente = Agente::firstWhere('user_id', $id);
        if ($agente->visura_camerale) \Storage::delete($agente->visura_camerale);
        $agente->delete();

        $record = User::find($id);
        abort_if(!$record, 404, 'Questo agente non esiste');
        $record->delete();


        return [
            'success' => true,
            'redirect' => action([AgenteController::class, 'index']),
        ];
    }


    public function azioni(Request $request, $id, $azione)
    {
        $u = User::find($id);
        if (!$u) {
            return ['success' => false, 'message' => 'Questo utente non esiste'];
        }
        switch ($azione) {

            case 'abilita-sms':
                $u->forceFill([
                    'two_factor_secret' => encrypt($this->provider->generateSecretKey()),
                    'two_factor_recovery_codes' => encrypt(json_encode(Collection::times(8, function () {
                        return RecoveryCode::generate();
                    })->all())),
                ])->save();
                return ['success' => true, 'title' => 'Accesso con verifica sms', 'message' => 'La verifica accesso sms è stata abilitata'];

            case 'disabilita-sms':
                if (!is_null($u->two_factor_secret) ||
                    !is_null($u->two_factor_recovery_codes) ||
                    !is_null($u->two_factor_confirmed_at)) {
                    $u->forceFill([
                            'two_factor_secret' => null,
                            'two_factor_recovery_codes' => null,
                        ] + (Fortify::confirmsTwoFactorAuthentication() ? [
                            'two_factor_confirmed_at' => null,
                        ] : []))->save();
                }
                return ['success' => true, 'title' => 'Accesso con verifica sms', 'message' => 'La verifica accesso sms è stata disabilitata'];

            case 'sospendi':
                $u->syncPermissions([]);
                return ['success' => true, 'redirect' => action([AgenteController::class, 'index'])];

            case 'impersona':
                return $this->azioneImpersona($id);

            case 'invia-mail-password-reset':
                return $this->azioneInviaMailPassowrdReset($id);

            case 'resetta-password':
                $user = User::find($id);
                $user->password = bcrypt('123456');
                $user->save();
                return ['success' => true, 'title' => 'Password impostata', 'message' => 'La password è stata impostata a 123456'];

            case 'imposta_mandato':
                $mandatoId = $request->input('mandato');
                $stato = $request->input('stato');
                $record = Mandato::where('agente_id', $id)->where('gestore_id', $mandatoId)->first();
                if (!$record) {
                    $record = new Mandato();
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
                return ['success' => false, 'title' => 'Azione sconosciouta', 'message' => 'Azione ' . $azione . ' non prevista'];

        }

    }


    public function tab($id, $tab)
    {
        $cliente = User::find($id);

        abort_if(!$cliente, 404);
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
     * @param User $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDatiUtente($model, $request)
    {

        $nuovo = !$model->id;

        if ($nuovo) {
            $model->password = Hash::make(Str::uuid());
        }

        //Ciclo su campi
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


        $model->syncPermissions(array_merge([$request->input('ruolo')], $request->input('vedi', [])));


        if ($nuovo) {
            dispatch(function () use ($model, $password) {
                $model->notify(new DatiAccessoNotification($password));

            })->afterResponse();

        }

        return $model;
    }

    /**
     * @param Agente $model
     * @param Request $request
     * @param User $user
     * @return mixed
     */
    protected function salvadatiAgente($model, $request, $user)
    {

        $nuovo = !$model->id;

        if ($nuovo) {
            $model->user_id = $user->id;
        }

        //Ciclo su campi
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

        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }
        $model->save();

        $user->alias = $model->ragione_sociale ?: ($user->cognome . ' ' . $user->nome);
        $user->save();

        if ($request->file('visura_camerale')) {

            $tmpFile = $request->file('visura_camerale');
            $extensione = $tmpFile->getClientOriginalExtension();
            $filename = hexdec(uniqid()) . '.' . $extensione;
            $cartella = config('configurazione.visure_camerali.cartella');
            $tmpFile->storeAs($cartella, $filename);
            $model->visura_camerale = $cartella . '/' . $filename;
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
        return \App\Models\Agente::get();
    }


    protected function rules($id = null)
    {

        $rules = [
            'nome' => ['required', 'max:255'],
            'cognome' => ['required', 'max:255'],
            'codice_fiscale' => ['nullable', new CodiceFiscaleRule()],
            'partita_iva' => ['nullable', new PartitaIvaRule()],
            'iban' => ['nullable', new \App\Rules\IbanRule()],
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
                new TelefonoRule(),
                Rule::unique(User::class)->ignore($id),
            ],
            'password' => [$id == null ? 'required' : 'nullable', 'string', new PasswordRules()],

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

        Session::flash('impersona', Auth::id());
        Auth::loginUsingId($id, false);
        return ['success' => true, 'redirect' => '/'];
    }

    protected function azioneInviaMailPassowrdReset($id)
    {
        $user = User::find($id);
        dispatch(function () use ($user) {
            $token = Password::broker('new_users')->createToken($user);
            $user->notify(new PasswordResetNotification($token));
        })->afterResponse();
        return ['success' => true, 'title' => 'Email inviata', 'message' => 'La mail con il link per impostare la password è stata inviata all\'indirizzo ' . $user->email];

    }


    protected function queryProduzione($id)
    {
        return ProduzioneOperatore::where('user_id', $id)->orderByDesc('anno')->orderByDesc('mese')->paginate();
    }


}
