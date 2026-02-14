<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Operatore;
use App\Models\RegistroLogin;
use App\Models\User;
use App\Notifications\DatiAccessoNotification;
use App\Notifications\PasswordResetNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use function App\mese;

class OperatoreController extends Controller
{

    protected $ruoli = ['operatore' => 'Operatore', 'teamleader' => 'Team leader', 'supervisore' => 'Supervisore', 'admin' => 'Amministratore'];
    protected $ruolo;
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


        $ordinamenti = [
            'recente' => ['testo' => 'Più recente', 'filtro' => function ($q) {
                return $q->orderBy('id', 'desc');
            }],

            'nominativo' => ['testo' => 'Nominativo', 'filtro' => function ($q) {
                return $q->orderBy('cognome')->orderBy('name');
            }]

        ];


        /** @var User|null $authUser */
        $authUser = Auth::user();
        $orderByUser = $authUser?->getExtra($nomeClasse);
        $orderByString = $request->input('orderBy');

        if ($orderByString) {
            $orderBy = $orderByString;
        } else if ($orderByUser) {
            $orderBy = $orderByUser;
        } else {
            $orderBy = 'recente';
        }

        if ($authUser instanceof User && $orderByUser != $orderByString) {
            $authUser->setExtra([$nomeClasse => $orderBy]);
        }

        //Applico ordinamento
        $recordsQB = call_user_func($ordinamenti[$orderBy]['filtro'], $recordsQB);

        if ($request->ajax()) {

            $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();


            return [
                'html' => base64_encode(view('Backend.Operatore.tabella', [
                    'records' => $records,
                    'controller' => OperatoreController::class,
                    'colonnaTeamleader' => $this->ruolo == 'operatore',
                    'colonnaOperatori' => $this->ruolo == 'teamleader'

                ])->render())
            ];


        }


        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        return view('Backend.Operatore.index', [
            'records' => $records,
            'titoloPagina' => 'Elenco ' . $this->titoloPaginaIndex(),
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'controller' => OperatoreController::class,
            'ruoliApplicabili' => array_keys($this->ruoliApplicabili()),
            'colonnaTeamleader' => $this->ruolo == 'operatore',
            'colonnaOperatori' => $this->ruolo == 'teamleader',
            'conFiltro' => $this->conFiltro,
        ]);
    }


    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {
        $where = false;

        $queryBuilder = User::where('id', '>', 1)->with('permissions');

        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',name,cognome,email)'), 'like', "%$t%");
            }
        }


        if ($where) {
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
        $nomeClasse = get_class($this);

        return view('Backend.Operatore.edit', [
            'record' => new User(),
            'titoloPagina' => 'Nuovo ' . User::NOME_SINGOLARE,
            'controller' => $nomeClasse,
            'ruoli' => $this->ruoliApplicabili(),
            'breadcrumbs' => [action([$nomeClasse, 'index']) => 'Elenco ' . User::NOME_PLURALE]


        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
        * @return mixed
     */
    public function store(Request $request)
    {
        $request->validate($this->rules(null));
        $this->salvaDati(new User(), $request, __FUNCTION__);
        return $this->backToIndex();
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
        * @return mixed
     */
    public function show($id)
    {

        $record = User::find($id);
        abort_if(!$record, 404, 'Questo operatore non esiste');
        $controller = get_class($this);


        $records = RegistroLogin::where('user_id', $record->id)->latest()->paginate();
        $ultimoAccesso = $records[0];
        return view('Backend.Operatore.show', [
            'record' => $record,
            'ultimoAccesso' => $ultimoAccesso,
            'titoloPagina' => $record->nominativo(),
            'controller' => $controller,
            'breadcrumbs' => [action([$controller, 'index']) => 'Torna a elenco ' . \App\Models\User::NOME_PLURALE],
            'records'=>$records
        ]);
    }


    public function tab($id, $tab)
    {
        switch ($tab) {
            case 'ore-lavorate':
                return $this->taboreMese($id);

            case 'login':
                return $this->tabLogin($id);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
        * @return mixed
     */
    public function edit($id)
    {
        $record = User::find($id);
        if (!$record) {
            abort(404, 'Questo operatore non esiste');
        }

        /** @var User|null $authUser */
        $authUser = Auth::user();
        if ($record->can('admin') && !($authUser?->can('admin') ?? false)) {
            abort(403, 'Non hai il permesso per effettuare questa operazione');
        }

        return view('Backend.Operatore.edit', [
            'record' => $record,
            'titoloPagina' => 'Modifica ' . User::NOME_SINGOLARE . ' ' . $record->nominativo(),
            'controller' => get_class($this),
            'ruoli' => $this->ruoliApplicabili()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
        * @return mixed
     */
    public function update(Request $request, $id)
    {
        $record = User::find($id);
        if (!$record) {
            abort(404);
        }
        $request->validate($this->rules($id));
        $this->salvaDati($record, $request, __FUNCTION__);
        return $this->backToIndex();

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return array
     */
    public function destroy($id)
    {
        $u = User::find($id);
        if (!$u) {
            return ['success' => false, 'message' => 'Questo utente non esiste'];
        }
        //$u->delete();
        return ['success' => true, 'redirect' => action([OperatoreController::class, 'index'])];
    }


    public function azioni($id, $azione)
    {
        $u = User::visibili()->find($id);
        if (!$u) {
            return ['success' => false, 'message' => 'Questo utente non esiste'];
        }
        switch ($azione) {
            case 'sospendi':
                $p = Permission::findByName('sospeso');
                $u->syncPermissions([$p]);
                return ['success' => true, 'redirect' => action([OperatoreController::class, 'index'])];

            case 'impersona':
                return $this->azioneImpersona($id);

            case 'invia-mail-password-reset':
                return $this->azioneInviaMailPasswordReset($id);

            case 'resetta-password':
                return $this->azioneResettaPassword($id);


        }

    }

    /**
     * @param User $model
     * @param Request $request
     * @param string $function
     * @return mixed
     */
    protected function salvaDati($model, $request, $function)
    {

        //Ciclo su campi '
        $nuovo = !$model->id;

        if ($nuovo) {
            $model->password = Hash::make(Str::uuid());

        }

        $campi = [
            'name' => '',
            'email' => '',
            'cognome' => '',
            'telefono' => 'app\getInputTelefono',
            'codice_fiscale' => '',
        ];

        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }
        $model->save();


        $model->syncPermissions([$request->input('ruolo')]);


        if ($nuovo) {
            dispatch(function () use ($model) {
                /** @var \Illuminate\Auth\Passwords\PasswordBroker $passwordBroker */
                $passwordBroker = Password::broker('new_users');
                $token = $passwordBroker->createToken($model);
                $model->notify(new PasswordResetNotification($token));

            })->afterResponse();

        }


        return $model;

    }


    protected function rules($userId)
    {

        //https://github.com/lucasvdh/laravel-iban

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'cognome' => ['required', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:255'],
        ];

        if ($userId) {
            $rules           ['email'] = [
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                Rule::unique(User::class)->ignore($userId),
            ];

        } else {
            $rules           ['email'] = [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ];

        }


        return $rules;
    }

    protected function backToIndex()
    {
        return redirect()->action([OperatoreController::class, 'index']);
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

    protected function azioneInviaMailPasswordReset($id)
    {

        $user = User::find($id);

        if (!$user || !$user->email) {
            Log::warning('OperatoreController: invio reset password non eseguito, email non valida', [
                'azione' => 'invia-mail-password-reset',
                'operatore_id' => $id,
                'utente_trovato' => (bool)$user,
            ]);
            return ['success' => false, 'title' => 'Email non valida', 'message' => 'L\'operatore non ha un indirizzo email valido.'];
        }

        try {
            /** @var \Illuminate\Auth\Passwords\PasswordBroker $passwordBroker */
            $passwordBroker = Password::broker('new_users');
            $token = $passwordBroker->createToken($user);
            $user->notify(new PasswordResetNotification($token));
            $sentAt = now()->format('d/m/Y H:i:s');
            Log::info('OperatoreController: email reset password inviata', [
                'azione' => 'invia-mail-password-reset',
                'operatore_id' => $user->id,
                'email' => $user->email,
                'sent_at' => $sentAt,
            ]);
        } catch (\Throwable $e) {
            report($e);
            Log::error('OperatoreController: errore invio email reset password', [
                'azione' => 'invia-mail-password-reset',
                'operatore_id' => $user->id,
                'email' => $user->email,
                'errore' => $e->getMessage(),
            ]);
            return ['success' => false, 'title' => 'Invio fallito', 'message' => 'Errore durante invio email: ' . $e->getMessage()];
        }

        return [
            'success' => true,
            'title' => 'Email inviata',
            'message' => 'La mail con il link per impostare la password è stata inviata all\'indirizzo ' . $user->email,
            'sent_at' => $sentAt,
        ];


    }

    protected function azioneResettaPassword($id)
    {
        $user = User::find($id);
        if (!$user || !$user->email) {
            Log::warning('OperatoreController: reset password non eseguito, email non valida', [
                'azione' => 'resetta-password',
                'operatore_id' => $id,
                'utente_trovato' => (bool)$user,
            ]);
            return ['success' => false, 'title' => 'Email non valida', 'message' => 'L\'operatore non ha un indirizzo email valido.'];
        }

        $nuovaPassword = '123456';
        $user->password = bcrypt($nuovaPassword);
        $user->save();

        try {
            $user->notify(new DatiAccessoNotification($nuovaPassword));
            $sentAt = now()->format('d/m/Y H:i:s');
            Log::info('OperatoreController: password resettata e email inviata', [
                'azione' => 'resetta-password',
                'operatore_id' => $user->id,
                'email' => $user->email,
                'sent_at' => $sentAt,
            ]);
        } catch (\Throwable $e) {
            report($e);
            Log::error('OperatoreController: errore invio email dopo reset password', [
                'azione' => 'resetta-password',
                'operatore_id' => $user->id,
                'email' => $user->email,
                'errore' => $e->getMessage(),
            ]);
            return ['success' => false, 'title' => 'Password impostata', 'message' => 'Password aggiornata ma invio email fallito: ' . $e->getMessage()];
        }

        return [
            'success' => true,
            'title' => 'Password impostata',
            'message' => 'La password è stata impostata a 123456 e inviata via email a ' . $user->email,
            'sent_at' => $sentAt,
        ];

    }

    protected function tabLogin($id)
    {
        $record = User::find($id);
        return view('Backend.Operatore.show.tabLogin', [
            'record' => $record
        ]);
    }

    protected function taboreMese($id)
    {

        $dataDa = Carbon::today()->firstOfMonth();
        $dataA = $dataDa->copy()->endOfMonth();
        $record = User::find($id);
        return view('Backend.Operatore.show.tabOreMese', [
            'record' => $record,
            'records' => collect(),
            'titoloPagina' => 'Ore lavorate ' . mese($dataDa->month) . ' ' . $dataDa->year
        ]);
    }

    protected function ruoliApplicabili()
    {

        return Permission::get()->pluck('name')->toArray();
        $ruoli = $this->ruoli;
        /** @var User|null $authUser */
        $authUser = Auth::user();
        if ($authUser?->can('teamleader')) {
            $ruoli = [];
        } elseif ($authUser?->can('supervisore')) {
            unset($ruoli['admin']);
            unset($ruoli['supervisore']);
        }

        return $ruoli;
    }


    protected function titoloPaginaIndex()
    {
        switch ($this->ruolo) {
            case 'operatore':
                return 'operatori';

            case 'teamleader':
                return 'teamleaders';

            case 'supervisore':
                return 'supervisori';

            case 'admin':
                return 'admin';

            case 'sospeso':
                return 'sospesi';

        }
    }


}
