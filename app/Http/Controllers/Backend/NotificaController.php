<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Mail\NotificaMail;
use App\Models\User;
use App\Notifications\NotificaAgenteCambioEsitoContratto;
use App\Notifications\NotificaMessaggioPerAgenti;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Models\Notifica;

class NotificaController extends Controller
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

        $ordinamenti = [
            'recente' => ['testo' => 'Più recente', 'filtro' => function ($q) {
                return $q->orderBy('id', 'desc');
            }],

            'nominativo' => ['testo' => 'Nominativo', 'filtro' => function ($q) {
                return $q->orderBy('cognome')->orderBy('nome');
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

        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        if ($request->ajax()) {

            return [
                'html' => base64_encode(view('Backend.Notifica.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ])->render())
            ];

        }


        return view('Backend.Notifica.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\Notifica::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuova ' . \App\Models\Notifica::NOME_SINGOLARE,
            'testoCerca' => null

        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\Notifica::query()
            ->withCount('letture');

        //$this->conFiltro = true;
        return $queryBuilder;
    }


    /**
     * Show the form for creating a new resource.
     *
        * @return mixed
     */
    public function create()
    {
        $record = new Notifica();
        return view('Backend.Notifica.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . Notifica::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([NotificaController::class, 'index']) => 'Torna a elenco ' . Notifica::NOME_PLURALE]

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
        $record = new Notifica();
        $this->salvaDati($record, $request);
        $emails = $this->resolveRecipientEmails($request);

        dispatch(function () use ($record, $emails) {
            foreach ($emails as $email) {
                Mail::to($email)->send(new NotificaMail($record));
            }
        })->afterResponse();

        return $this->backToIndex('Notifica inviata a ' . count($emails) . ' destinatari');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
        * @return mixed
     */
    public function show($id)
    {
        $record = Notifica::find($id);
        abort_if(!$record, 404, 'Questa notifica non esiste');
        return view('Backend.Notifica.show', [
            'record' => $record,
            'controller' => NotificaController::class,
            'titoloPagina' => Notifica::NOME_SINGOLARE,
            'breadcrumbs' => [action([NotificaController::class, 'index']) => 'Torna a elenco ' . Notifica::NOME_PLURALE]

        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
        * @return mixed
     */
    public function edit($id)
    {
        $record = Notifica::find($id);
        abort_if(!$record, 404, 'Questa notifica non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Backend.Notifica.edit', [
            'record' => $record,
            'controller' => NotificaController::class,
            'titoloPagina' => 'Modifica ' . Notifica::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([NotificaController::class, 'index']) => 'Torna a elenco ' . Notifica::NOME_PLURALE]

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
        $record = Notifica::find($id);
        abort_if(!$record, 404, 'Questa ' . Notifica::NOME_SINGOLARE . ' non esiste');
        $request->validate($this->rules($id));
        $this->salvaDati($record, $request);
        return $this->backToIndex();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
        * @return mixed
     */
    public function destroy($id)
    {
        $record = Notifica::find($id);
        abort_if(!$record, 404, 'Questa notifica non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([NotificaController::class, 'index']),
        ];
    }

    /**
     * @param Notifica $model
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
            'titolo' => 'app\getInputUcfirst',
            'testo' => '',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }
        $model->destinatario = $request->input('destinatario', 'agente');
        $model->save();
        return $model;
    }

    protected function backToIndex(?string $status = null)
    {
        $redirect = redirect()->action([get_class($this), 'index']);
        if ($status !== null && $status !== '') {
            $redirect->with('status', $status);
        }

        return $redirect;
    }

    protected function resolveRecipientEmails(Request $request): array
    {
        $destinatario = (string)$request->input('destinatario', 'agente');

        $users = User::query()
            ->whereNotNull('email')
            ->where('email', '!=', '');

        if (in_array($destinatario, ['agente', 'operatore', 'admin'], true)) {
            $users->whereHas('permissions', function ($query) use ($destinatario) {
                $query->where('name', $destinatario);
            });
        }

        $emails = $users->pluck('email')
            ->map(fn($email) => strtolower(trim((string)$email)))
            ->filter(fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->values()
            ->all();

        $extraRaw = (string)$request->input('emails_aggiuntive', '');
        if ($extraRaw !== '') {
            $extra = preg_split('/[;,\s]+/', $extraRaw) ?: [];
            foreach ($extra as $email) {
                $email = strtolower(trim((string)$email));
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
                    $emails[] = $email;
                }
            }
        }

        return array_values(array_unique($emails));
    }

    /** Query per index
     * @return array
     */
    protected function queryBuilderIndexSemplice()
    {
        return \App\Models\Notifica::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'titolo' => ['required', 'max:255'],
            'testo' => ['required'],
            'destinatario' => ['required', 'in:agente,operatore,admin,tutti'],
            'emails_aggiuntive' => ['nullable', 'string'],
        ];

        return $rules;
    }

}
