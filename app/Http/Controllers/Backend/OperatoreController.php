<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Operatore;
use App\Models\RegistroLogin;
use App\Models\User;
use App\Notifications\NuovoUtenteInfoNotification;
use App\Notifications\PasswordResetNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        if ($request->ajax()) {

            $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();


            return [
                'html' => base64_encode(view('Backend.Operatore.tabella', [
                    'records' => $records,
                    'controller' => OperatoreController::class,
                    'colonnaTeamleader' => $this->ruolo == 'operatore',
                    'colonnaOperatori' => $this->ruolo == 'teamleader'

                ]))
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
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $record = User::find($id);
        if (!$record) {
            abort(404, 'Questo operatore non esiste');
        }

        if ($record->hasPermissionTo('admin') && !Auth::user()->hasPermissionTo('admin')) {
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
     * @return \Illuminate\Http\Response
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
                return $this->azioneInviaMailPassowrdReset($id);

            case 'resetta-password':
                $user = User::find($id);
                $user->password = bcrypt('123456');
                $user->save();
                return ['success' => true, 'title' => 'Password impostata', 'message' => 'La password è stata impostata a 123456'];


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
                $token = Password::broker('new_users')->createToken($model);
                $model->notify(new NuovoUtenteInfoNotification($token));

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

    protected function azioneInviaMailPassowrdReset($id)
    {

        $user = User::find($id);

        dispatch(function () use ($user) {
            $token = Password::broker('new_users')->createToken($user);
            $user->notify(new PasswordResetNotification($token));

        })->afterResponse();
        return ['success' => true, 'title' => 'Email inviata', 'message' => 'La mail con il link per impostare la password è stata inviata all\'indirizzo ' . $user->email];


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
            'records' => \App\Models\OraLavorata::orderBy('data')->where('user_id', $record->id)->whereDate('data', '>=', $dataDa)->whereDate('data', '<=', $dataA)->get(),
            'titoloPagina' => 'Ore lavorate ' . mese($dataDa->month) . ' ' . $dataDa->year
        ]);
    }

    protected function ruoliApplicabili()
    {

        return Permission::get()->pluck('name')->toArray();
        $ruoli = $this->ruoli;
        if (Auth::user()->hasPermissionTo('teamleader')) {
            $ruoli = [];
        } elseif (Auth::user()->hasPermissionTo('supervisore')) {
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
