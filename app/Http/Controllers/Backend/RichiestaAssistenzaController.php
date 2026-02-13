<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RichiestaAssistenza;
use DB;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;


class RichiestaAssistenzaController extends Controller
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
                'html' => base64_encode(view('Backend.RichiestaAssistenza.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];
        }


        return view('Backend.RichiestaAssistenza.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\RichiestaAssistenza::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuova ' . \App\Models\RichiestaAssistenza::NOME_SINGOLARE,
            'testoCerca' => 'Cerca in cognome, nome, codice fiscale, email'

        ]);

    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\RichiestaAssistenza::query()
            ->with('prodotto:id,nome')
            ->with('cliente');
        $term = $request->input('cerca');
        if ($term) {
            $queryBuilder->whereHas('cliente', function ($q) use ($term) {
                $arrTerm = explode(' ', $term);
                foreach ($arrTerm as $t) {
                    $q->where(DB::raw('concat_ws(\' \',cognome,nome,codice_fiscale,email)'), 'like', "%$t%");
                }
            });
        }

        //$this->conFiltro = true;
        return $queryBuilder;
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
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

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate($this->rules(null));
        $record = new RichiestaAssistenza();
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
        $record = RichiestaAssistenza::find($id);
        abort_if(!$record, 404, 'Questa richiestaassistenza non esiste');
        return view('Backend.RichiestaAssistenza.show', [
            'record' => $record,
            'controller' => RichiestaAssistenzaController::class,
            'titoloPagina' => RichiestaAssistenza::NOME_SINGOLARE,
            'breadcrumbs' => [action([RichiestaAssistenzaController::class, 'index']) => 'Torna a elenco ' . RichiestaAssistenza::NOME_PLURALE]

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

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $record = RichiestaAssistenza::find($id);
        abort_if(!$record, 404, 'Questa ' . RichiestaAssistenza::NOME_SINGOLARE . ' non esiste');
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
        $record = RichiestaAssistenza::find($id);
        abort_if(!$record, 404, 'Questa richiestaassistenza non esiste');

        $record->delete();


        return [
            'success' => true,
            'redirect' => action([RichiestaAssistenzaController::class, 'index']),
        ];
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

    /**
     * @param RichiestaAssistenza $model
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

    protected function backToIndex()
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
