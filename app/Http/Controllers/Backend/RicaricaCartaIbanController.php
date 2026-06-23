<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\RicaricaCartaIban;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RicaricaCartaIbanController extends Controller
{
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
            }],
        ];

        /** @var User|null $currentUser */
        $currentUser = Auth::user();
        $orderByUser = $currentUser ? $currentUser->getExtra($nomeClasse) : null;
        $orderByString = $request->input('orderBy');

        if ($orderByString) {
            $orderBy = $orderByString;
        } elseif ($orderByUser) {
            $orderBy = $orderByUser;
        } else {
            $orderBy = 'recente';
        }

        if ($currentUser && $orderByUser != $orderByString && $orderByString) {
            $currentUser->setExtra([$nomeClasse => $orderBy]);
        }

        $recordsQB = call_user_func($ordinamenti[$orderBy]['filtro'], $recordsQB);
        $records = $recordsQB->paginate(config('configurazione.paginazione'))->withQueryString();

        if ($request->ajax()) {
            return [
                'html' => base64_encode(view('Backend.RicaricaCartaIban.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ])->render()),
            ];
        }

        return view('Backend.RicaricaCartaIban.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'IBAN Ricariche Carte Prepagate',
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'testoNuovo' => 'Nuovo IBAN',
            'testoCerca' => 'Cerca per cognome, nome, IBAN, codice fiscale',
        ]);
    }

    protected function applicaFiltri(Request $request)
    {
        $queryBuilder = RicaricaCartaIban::query();
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw("concat_ws(' ',cognome,nome,iban,codice_fiscale)"), 'like', "%$t%");
            }
        }

        return $queryBuilder;
    }

    public function create()
    {
        $record = new RicaricaCartaIban;

        return view('Backend.RicaricaCartaIban.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo IBAN',
            'controller' => get_class($this),
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco IBAN'],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->rules(null));
        $record = new RicaricaCartaIban;
        $this->salvaDati($record, $request);

        return redirect()->action([self::class, 'index']);
    }

    public function edit($id)
    {
        $record = RicaricaCartaIban::find($id);
        abort_if(! $record, 404, 'Record non trovato');

        $eliminabile = true;

        return view('Backend.RicaricaCartaIban.edit', [
            'record' => $record,
            'controller' => get_class($this),
            'titoloPagina' => 'Modifica IBAN - '.$record->nominativo(),
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco IBAN'],
        ]);
    }

    public function update(Request $request, $id)
    {
        $record = RicaricaCartaIban::find($id);
        abort_if(! $record, 404, 'Record non trovato');
        $request->validate($this->rules($id));
        $this->salvaDati($record, $request);

        return redirect()->action([self::class, 'index']);
    }

    public function destroy($id)
    {
        $record = RicaricaCartaIban::find($id);
        abort_if(! $record, 404, 'Record non trovato');
        $record->delete();

        return [
            'success' => true,
            'redirect' => action([self::class, 'index']),
        ];
    }

    protected function salvaDati(RicaricaCartaIban $model, Request $request): void
    {
        $campi = [
            'cognome' => 'app\getInputUcwords',
            'nome' => 'app\getInputUcwords',
            'codice_fiscale' => 'strtoupper',
            'telefono' => '',
            'email' => 'strtolower',
            'iban' => 'strtoupper',
            'intestatario_iban' => 'app\getInputUcwords',
            'carta' => '',
            'note' => '',
        ];

        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione !== '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        $model->save();
    }

    protected function rules($id = null): array
    {
        return [
            'cognome' => ['required', 'max:255'],
            'nome' => ['required', 'max:255'],
            'codice_fiscale' => ['nullable', 'max:255'],
            'telefono' => ['nullable', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'iban' => ['required', 'max:34', 'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/'],
            'intestatario_iban' => ['nullable', 'max:255'],
            'carta' => ['nullable', 'max:255'],
            'note' => ['nullable'],
        ];
    }
}
