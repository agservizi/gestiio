<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ContrattoTelefonia;
use App\Models\Notifica;
use App\Models\User;
use App\Notifications\NotificaDatiAccessoCliente;
use App\Notifications\NotificaDatiAccessoClienteContratto;
use App\Rules\PartitaIvaRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Cliente;
use DB;
use Illuminate\Support\Facades\Session;

class ClienteController extends Controller
{
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
                return $q->orderBy('cognome')->orderBy('nome');
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
                'html' => base64_encode(view('Backend.Cliente.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.Cliente.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\Cliente::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\Cliente::NOME_SINGOLARE,
            'testoCerca' => 'Cerca in nome, cognome, ragione sociale, codice fiscale'

        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\Cliente::query()
            ->with('utente');
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',nome,cognome,ragione_sociale,codice_fiscale)'), 'like', "%$t%");
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
        $record = new Cliente();
        return view('Backend.Cliente.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . Cliente::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([ClienteController::class, 'index']) => 'Torna a elenco ' . Cliente::NOME_PLURALE]

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
        $record = new Cliente();
        $this->salvaDati($record, $request);
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
        $record = Cliente::with('utente')->find($id);
        abort_if(!$record, 404, 'Questo cliente non esiste');

        return view('Backend.Cliente.show', [
            'record' => $record,
            'records' => $this->tabContrattiTelefoniaRecords($id, 'tab_contratti_telefonia'),
            'controller' => ClienteController::class,
            'titoloPagina' => ucfirst(Cliente::NOME_SINGOLARE) . ' ' . $record->nominativo(),
            'breadcrumbs' => [action([ClienteController::class, 'index']) => 'Torna a elenco ' . Cliente::NOME_PLURALE],
            'puoModificare' => ContrattoTelefonia::determinaPuoModificare(),
            'puoCambiareStato' => ContrattoTelefonia::determinaPuoCambiareStato(),
            'puoCreare' => ContrattoTelefonia::determinaPuoCreare(),

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
        $record = Cliente::find($id);
        abort_if(!$record, 404, 'Questo cliente non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Backend.Cliente.edit', [
            'record' => $record,
            'controller' => ClienteController::class,
            'titoloPagina' => 'Modifica ' . Cliente::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([ClienteController::class, 'index']) => 'Torna a elenco ' . Cliente::NOME_PLURALE]

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
        $record = Cliente::find($id);
        abort_if(!$record, 404, 'Questo ' . Cliente::NOME_SINGOLARE . ' non esiste');
        $request->validate($this->rules($id));
        $this->salvaDati($record, $request);
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
        $record = Cliente::find($id);
        abort_if(!$record, 404, 'Questo cliente non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([ClienteController::class, 'index']),
        ];
    }

    public function tab($id, $tab)
    {
        switch ($tab) {
            case 'tab_contratti_telefonia':
                return view('Backend.Cliente.show.tabContrattiTelefonia', [
                    'records' => $this->tabContrattiTelefoniaRecords($id, $tab),
                    'id' => $id,
                    'puoModificare' => ContrattoTelefonia::determinaPuoModificare(),
                    'puoCambiareStato' => ContrattoTelefonia::determinaPuoCambiareStato(),
                    'puoCreare' => ContrattoTelefonia::determinaPuoCreare(),
                    'controller' => ContrattoTelefoniaController::class

                ]);
        }
    }

    public function azioni(Request $request, $id, $azione)
    {
        $cliente = Cliente::find($id);
        if (!$cliente) {
            return ['success' => false, 'message' => 'Questo utente non esiste'];
        }
        switch ($azione) {

            case 'crea-utente':
                if (!$cliente->user_id) {
                    $user = new User();
                    $user->name = $cliente->nome;
                    $user->cognome = $cliente->cognome;
                    $user->email = $cliente->email;
                    $password = rand(11111111, 99999999);
                    $user->password = \Hash::make($password);
                    $user->telefono = $cliente->telefono;
                    $user->save();
                    $cliente->user_id = $user->id;
                    $cliente->save();

                    try {
                        $user->notify(new NotificaDatiAccessoCliente($password));
                        $user->invio_dati_accesso = now();
                        $user->save();

                    } catch (\Exception $exception) {
                        Notifica::notificaAdAdmin('Errore nell\'invio dati accesso cliente', 'a ' . $user->nominativo() . ': ' . $exception->getMessage(), 'error');

                    }


                    return ['success' => true, 'message' => 'Utente creato ed email inviata'];
                } else {
                    return ['success' => false, 'message' => 'Questo cliente ha già un utente'];
                }


            case 'invia-dati-accesso':

                $user = User::find($cliente->user_id);
                $password = rand(11111111, 99999999);
                $user->password = \Hash::make($password);
                $user->save();

                try {
                    $user->notify(new NotificaDatiAccessoCliente($password));
                    $user->invio_dati_accesso = now();
                    $user->save();

                } catch (\Exception $exception) {

                    return ['success' => false, 'message' => 'Errore nell\'invio della mail:' . $exception->getMessage()];

                }


                return ['success' => true, 'message' => 'mail inviata'];

            case 'impersona':
                return $this->azioneImpersona($cliente);

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
     * @param Cliente $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDati($model, $request)
    {

        $nuovo = !$model->id;

        if ($nuovo) {

        }

        //Ciclo su campi
        $campi = [
            'codice_fiscale' => 'strtoupper',
            'ragione_sociale' => 'app\getInputUcwords',
            'nome' => 'app\getInputUcwords',
            'cognome' => 'app\getInputUcwords',
            'email' => 'strtolower',
            'telefono' => '',
            'indirizzo' => '',
            'citta' => '',
            'cap' => '',
            'partita_iva' => '',
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
        return \App\Models\Cliente::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'codice_fiscale' => ['required', new \App\Rules\CodiceFiscaleRule()],
            'ragione_sociale' => ['nullable', 'max:255'],
            'nome' => ['required', 'max:255'],
            'cognome' => ['required', 'max:255'],
            'email' => ['nullable', 'max:255'],
            'telefono' => ['required', new \App\Rules\TelefonoRule()],
            'indirizzo' => ['nullable', 'max:255'],
            'citta' => ['nullable', 'max:255'],
            'cap' => ['nullable'],
            'partita_iva' => ['nullable', new PartitaIvaRule()],
        ];

        return $rules;
    }

    protected function azioneImpersona($cliente)
    {

        $user = User::find($cliente->user_id);
        if (!$user) {
            return ['success' => false, 'message' => 'Questo cliente non ha una utente'];
        }
        if ($user->hasPermissionTo('admin') && Auth::id() != 1) {
            return ['success' => false, 'message' => 'Non puoi impersonare questo utente'];
        }

        Session::flash('impersona', Auth::id());
        Auth::loginUsingId($user->id, false);
        return ['success' => true, 'redirect' => '/'];
    }

    protected function tabContrattiTelefoniaRecords($id, $tab)
    {
        return \App\Models\ContrattoTelefonia::query()
            ->whereRelation('cliente', 'id', $id)
            ->with(['comune' => function ($q) {
                $q->select('id', 'comune', 'targa');
            }])
            ->with(['esito' => function ($q) {
                $q->select('id', 'nome', 'colore_hex');
            }])
            ->with(['tipoContratto.gestore' => function ($q) {
                $q->select('id', 'nome', 'colore_hex');
            }])
            ->with(['tipoContratto' => function ($q) {
                $q->select('id', 'gestore_id', 'nome', 'pda');
            }])
            ->with(['agente' => function ($q) {
                $q->select('id', 'alias');
            }])
            ->withCount('allegati')
            ->latest('id')
            ->paginate()->withPath(action([ClienteController::class, 'tab'], ['id' => $id, 'tab' => $tab]));
    }

    protected function determinaPuoModificare()
    {
        return Auth::user()->hasAnyPermission(['admin', 'supervisore']);

    }

    protected function determinaPuoCambiareStato()
    {
        return $this->determinaPuoModificare() || Auth::user()->hasPermissionTo('supervisore');

    }

    protected function determinaPuoCreare()
    {
        return Auth::user()->hasAnyPermission(['admin', 'agente']);

    }


}
