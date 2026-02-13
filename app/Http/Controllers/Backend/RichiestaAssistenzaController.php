<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\RichiestaAssistenza;
use App\Models\User;
use DB;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;
use Illuminate\Contracts\View\View;


class RichiestaAssistenzaController extends Controller
{
    protected $conFiltro = false;


    public function index(Request $request): View|JsonResponse
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
            return response()->json([
                'html' => base64_encode(view('Backend.RichiestaAssistenza.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ])->render())
            ]);
        }


        return view('Backend.RichiestaAssistenza.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . RichiestaAssistenza::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuova ' . RichiestaAssistenza::NOME_SINGOLARE,
            'testoCerca' => 'Cerca in cognome, nome, codice fiscale, email',
            'prodotti' => Cache::remember('prodotti_assistenza', 3600, fn() => \App\Models\ProdottoAssistenza::pluck('nome', 'id'))

        ]);

    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\RichiestaAssistenza::query()
            ->select('id', 'cliente_id', 'prodotto_assistenza_id', 'created_at')
            ->with('prodotto:id,nome')
            ->with('cliente:id,nome,cognome,email,codice_fiscale');
        $term = $request->input('cerca');
        if ($term) {
            $queryBuilder->whereHas('cliente', function ($q) use ($term) {
                $arrTerm = explode(' ', $term);
                foreach ($arrTerm as $t) {
                    $q->where(function ($query) use ($t) {
                        $query->where('cognome', 'like', "%$t%")
                              ->orWhere('nome', 'like', "%$t%")
                              ->orWhere('codice_fiscale', 'like', "%$t%")
                              ->orWhere('email', 'like', "%$t%");
                    });
                }
            });
        }

        //$this->conFiltro = true;
        return $queryBuilder;
    }


    public function create(Request $request): View
    {
        $record = new RichiestaAssistenza();
        $record->cliente_id = $request->input('cliente_id');
        return view('Backend.RichiestaAssistenza.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . RichiestaAssistenza::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([RichiestaAssistenzaController::class, 'index']) => 'Torna a elenco ' . RichiestaAssistenza::NOME_PLURALE]

        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate($this->rules(null));
        $record = new RichiestaAssistenza();
        $this->salvaDati($record, $request);
        return $this->backToIndex();
    }

    public function show($id): View
    {
        $record = RichiestaAssistenza::find($id);
        abort_if(!$record, 404, 'Questa richiestaassistenza non esiste');
        return view('Backend.RichiestaAssistenza.show', [
            'record' => $record,
            'controller' => RichiestaAssistenzaController::class,
            'titoloPagina' => RichiestaAssistenza::NOME_SINGOLARE,
            'breadcrumbs' => [action([RichiestaAssistenzaController::class, 'index']) => 'Torna a elenco ' . RichiestaAssistenza::NOME_PLURALE]

        ]);
    }

    public function edit($id): View
    {
        $record = RichiestaAssistenza::find($id);
        abort_if(!$record, 404, 'Questa richiestaassistenza non esiste');
        if (false) {
            $eliminabile = 'Non eliminabile perchè presente in ...';
        } else {
            $eliminabile = true;
        }
        return view('Backend.RichiestaAssistenza.edit', [
            'record' => $record,
            'controller' => RichiestaAssistenzaController::class,
            'titoloPagina' => 'Modifica ' . RichiestaAssistenza::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([RichiestaAssistenzaController::class, 'index']) => 'Torna a elenco ' . RichiestaAssistenza::NOME_PLURALE]

        ]);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $record = RichiestaAssistenza::find($id);
        abort_if(!$record, 404, 'Questa ' . RichiestaAssistenza::NOME_SINGOLARE . ' non esiste');
        $request->validate($this->rules($id));
        $this->salvaDati($record, $request);
        return $this->backToIndex();
    }

    public function destroy($id): JsonResponse
    {
        $record = RichiestaAssistenza::find($id);
        abort_if(!$record, 404, 'Questa richiestaassistenza non esiste');

        $record->delete();


        return response()->json([
            'success' => true,
            'redirect' => action([RichiestaAssistenzaController::class, 'index']),
        ]);
    }

    public function pdf($id)
    {
        $richiesta = RichiestaAssistenza::with('cliente')->with('prodotto')->find($id);
        switch ($richiesta->prodotto_assistenza_id) {
            case 1:
                return $this->pdfNamirial($richiesta);

            case 2:
                return $this->pdfInfocert($richiesta);

        }

    }

    protected function pdfNamirial($richiesta)
    {
        $fpdf = new Fpdi();

        $pagecount = $fpdf->setSourceFile(public_path('/pdf/spid_namirial.pdf'));
        $tpl = $fpdf->importPage(1);
        $fpdf->AddPage();
        $fpdf->useTemplate($tpl);
        $fpdf->SetFont('Arial', 'B');

        $fpdf->SetFontSize('20'); // set font size
        $fpdf->SetAutoPageBreak(false);
        $fpdf->SetXY(60, 62);
        $fpdf->Cell(50, 8, $richiesta->nome_utente, 0, 0,);

        $fpdf->SetXY(60, 77);
        $fpdf->Cell(50, 8, $richiesta->password, 0, 0,);

        $fpdf->SetXY(9, 45);
        $fpdf->Cell(50, 8, $richiesta->pin, 0, 0,);

        return $fpdf->Output('D', 'spid_' . Str::slug($richiesta->cliente->codice_fiscale) . '.pdf');
    }

    protected function pdfInfocert($richiesta)
    {
        $fpdf = new Fpdi();

        $pagecount = $fpdf->setSourceFile(public_path('/pdf/spid_infocert.pdf'));
        $tpl = $fpdf->importPage(1);
        $fpdf->AddPage();
        $fpdf->useTemplate($tpl);
        $fpdf->SetFont('Arial', 'B');

        $fpdf->SetFontSize('20'); // set font size
        $fpdf->SetAutoPageBreak(false);
        $fpdf->SetXY(60, 32.5);
        $fpdf->Cell(50, 8, $richiesta->pin, 0, 0,);

        $fpdf->SetXY(60, 59);
        $fpdf->Cell(50, 8, $richiesta->nome_utente, 0, 0,);

        $fpdf->SetXY(60, 73.5);
        $fpdf->Cell(50, 8, $richiesta->password, 0, 0,);



        return $fpdf->Output('D', 'spid_' . Str::slug($richiesta->cliente->codice_fiscale) . '.pdf');
    }

    protected function salvaDati(RichiestaAssistenza $model, Request $request): RichiestaAssistenza
    {

        $nuovo = !$model->exists;

        if ($nuovo) {

        }

        //Ciclo su campi
        $campi = [
            'cliente_id' => '',
            'prodotto_assistenza_id' => '',
            'nome_utente' => '',
            'password' => '',
            'pin' => '',
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

    protected function backToIndex(): RedirectResponse
    {
        return redirect()->action([get_class($this), 'index']);
    }

    /** Query per index
     * @return array
     */
    protected function queryBuilderIndexSemplice()
    {
        return \App\Models\RichiestaAssistenza::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'cliente_id' => ['required'],
            'prodotto_assistenza_id' => ['required'],
            'nome_utente' => ['nullable', 'max:255'],
            'password' => ['nullable', 'max:255'],
            'pin' => ['nullable', 'max:255'],
        ];

        return $rules;
    }

}
