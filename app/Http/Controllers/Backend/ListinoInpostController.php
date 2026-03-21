<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ListinoInpost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListinoInpostController extends Controller
{
    protected $conFiltro = false;

    public function index(Request $request)
    {
        $nomeClasse = get_class($this);
        $recordsQB = $this->applicaFiltri($request);
        $records = $recordsQB->paginate(config('configurazione.paginazione'));
        $records->appends($request->query());

        if ($request->ajax()) {
            return [
                'html' => base64_encode(view('Backend.ListinoInpost.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ])->render()),
            ];
        }

        return view('Backend.ListinoInpost.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . ListinoInpost::NOME_PLURALE,
            'orderBy' => null,
            'ordinamenti' => null,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . ListinoInpost::NOME_SINGOLARE,
            'testoCerca' => null,
        ]);
    }

    protected function applicaFiltri($request)
    {
        $queryBuilder = ListinoInpost::query()->orderByRaw("FIELD(package_type, 'small', 'medium', 'large')");
        $term = $request->input('cerca');
        if ($term) {
            foreach (explode(' ', $term) as $t) {
                $queryBuilder->where(DB::raw("concat_ws(' ',package_type)"), 'like', "%$t%");
            }
        }

        return $queryBuilder;
    }

    public function create()
    {
        $record = new ListinoInpost();

        return view('Backend.ListinoInpost.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . ListinoInpost::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco ' . ListinoInpost::NOME_PLURALE],
            'nascondiToolbar' => true,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate($this->rules());
        $record = new ListinoInpost();
        $this->salvaDati($record, $request);

        return $this->backToIndex();
    }

    public function show($id)
    {
        $record = ListinoInpost::find($id);
        abort_if(!$record, 404, 'Questo listino InPost non esiste');

        return view('Backend.ListinoInpost.show', [
            'record' => $record,
            'controller' => self::class,
            'titoloPagina' => ListinoInpost::NOME_SINGOLARE,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco ' . ListinoInpost::NOME_PLURALE],
            'nascondiToolbar' => true,
        ]);
    }

    public function edit($id)
    {
        $record = ListinoInpost::find($id);
        abort_if(!$record, 404, 'Questo listino InPost non esiste');
        $eliminabile = true;

        return view('Backend.ListinoInpost.edit', [
            'record' => $record,
            'controller' => self::class,
            'titoloPagina' => 'Modifica ' . ListinoInpost::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([self::class, 'index']) => 'Torna a elenco ' . ListinoInpost::NOME_PLURALE],
            'nascondiToolbar' => true,
        ]);
    }

    public function update(Request $request, $id)
    {
        $record = ListinoInpost::find($id);
        abort_if(!$record, 404, 'Questo listino InPost non esiste');
        $request->validate($this->rules());
        $this->salvaDati($record, $request);

        return $this->backToIndex();
    }

    public function destroy($id)
    {
        $record = ListinoInpost::find($id);
        abort_if(!$record, 404, 'Questo listino InPost non esiste');
        $record->delete();

        return [
            'success' => true,
            'redirect' => action([self::class, 'index']),
        ];
    }

    protected function salvaDati(ListinoInpost $model, Request $request): ListinoInpost
    {
        $campi = [
            'package_type' => '',
            'locker_point' => 'app\getInputNumero',
            'home_delivery' => 'app\getInputNumero',
        ];

        foreach ($campi as $campo => $funzione) {
            $valore = $request->{$campo};
            if ($funzione !== '') {
                $valore = $funzione($valore);
            }
            $model->{$campo} = $valore;
        }

        $model->save();

        return $model;
    }

    protected function backToIndex()
    {
        return redirect()->action([get_class($this), 'index']);
    }

    protected function rules(): array
    {
        return [
            'package_type' => ['required', 'in:small,medium,large'],
            'locker_point' => ['nullable'],
            'home_delivery' => ['nullable'],
        ];
    }
}
