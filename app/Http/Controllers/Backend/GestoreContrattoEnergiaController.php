<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\GestoreContrattoEnergiaContrattoEnergia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\GestoreContrattoEnergia;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class GestoreContrattoEnergiaController extends Controller
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
                'html' => base64_encode(view('Backend.GestoreContrattoEnergia.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.GestoreContrattoEnergia.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\GestoreContrattoEnergia::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\GestoreContrattoEnergia::NOME_SINGOLARE,
            'testoCerca' => null

        ]);


    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\GestoreContrattoEnergia::query();
        $term = $request->input('cerca');
        if ($term) {
            $arrTerm = explode(' ', $term);
            foreach ($arrTerm as $t) {
                $queryBuilder->where(DB::raw('concat_ws(\' \',nome)'), 'like', "%$t%");
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
        $record = new GestoreContrattoEnergia();
        return view('Backend.GestoreContrattoEnergia.edit', [
            'record' => $record,
            'titoloPagina' => 'Nuovo ' . GestoreContrattoEnergia::NOME_SINGOLARE,
            'controller' => get_class($this),
            'breadcrumbs' => [action([GestoreContrattoEnergiaController::class, 'index']) => 'Torna a elenco ' . GestoreContrattoEnergia::NOME_PLURALE]

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
        $record = new GestoreContrattoEnergia();
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $record = GestoreContrattoEnergia::find($id);
        abort_if(!$record, 404, 'Questo gestore non esiste');
        if (Auth::id() > 1) {
            $eliminabile = 'Non eliminabile';
        } else {
            $eliminabile = true;
        }

        return view('Backend.GestoreContrattoEnergia.edit', [
            'record' => $record,
            'controller' => GestoreContrattoEnergiaController::class,
            'titoloPagina' => 'Modifica ' . GestoreContrattoEnergia::NOME_SINGOLARE,
            'eliminabile' => $eliminabile,
            'breadcrumbs' => [action([GestoreContrattoEnergiaController::class, 'index']) => 'Torna a elenco ' . GestoreContrattoEnergia::NOME_PLURALE]

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
        $record = GestoreContrattoEnergia::find($id);
        abort_if(!$record, 404, 'Questo ' . GestoreContrattoEnergia::NOME_SINGOLARE . ' non esiste');
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
        $record = GestoreContrattoEnergia::find($id);
        abort_if(!$record, 404, 'Questo gestore non esiste');

        if ($record->logo) {
            Storage::delete($record->logo);
        }
        $record->delete();


        return [
            'success' => true,
            'redirect' => action([GestoreContrattoEnergiaController::class, 'index']),
        ];
    }

    /**
     * @param GestoreContrattoEnergia $model
     * @param Request $request
     * @return mixed
     */
    protected function salvaDati($model, $request)
    {

        $nuovo = !$model->id;

        if ($nuovo) {
            $model->model_prodotto = 'ProdottoEnergiaGenerico';
        }

        //Ciclo su campi
        $campi = [
            'nome' => 'app\getInputUcwords',
            'colore_hex' => '',
            'url' => 'strtolower',
            'attivo' => 'app\getInputCheckbox',
            'includi_dati_contratto' => 'app\getInputCheckbox',
            'email_notifica_a_gestore' => 'strtolower',
            'titolo_notifica_a_gestore' => '',
            'testo_notifica_a_gestore' => '',
            'importo_contratto' => 'app\getInputNumero',
            'importo_pagamento_bollettino' => 'app\getInputNumero',
        ];
        foreach ($campi as $campo => $funzione) {
            $valore = $request->$campo;
            if ($funzione != '') {
                $valore = $funzione($valore);
            }
            $model->$campo = $valore;
        }

        if (Auth::id() == 1) {
            $model->model_prodotto = $request->input('model_prodotto');
        }

        $model->save();

        if ($request->file('logo')) {
            $tmpFile = $request->file('logo');
            $extensione = $tmpFile->extension();
            $filename = hexdec(uniqid()) . '.' . $extensione;
            if ($model->logo && \Storage::exists($model->logo)) {
                \Storage::delete($model->logo);
            }
            $fileImmagine = $this->salvaImmagine($tmpFile, $filename, true);
            $model->logo = $fileImmagine;
            $model->save();
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
        return \App\Models\GestoreContrattoEnergia::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'nome' => ['required', 'max:255'],
            'colore_hex' => ['nullable', 'max:255'],
            'attivo' => ['nullable'],
            'email_notifica_a_gestore' => ['nullable']
        ];

        return $rules;
    }

    protected function salvaImmagine($tmpFile, $nomefile, $canvas = false)
    {

        $cartella = config('configurazione.loghi.cartella');
        if (!Storage::exists($cartella)) {
            Storage::makeDirectory($cartella);
        }


        $img = Image::make($tmpFile);
        $dimensioni = config('configurazione.loghi.dimensioni');
        //$img->fit($dimensioni['width'], $dimensioni['height'], null, 'center');
        $img = $this->ridimensionaImmagine($img, $dimensioni['width'], $dimensioni['height'], $canvas, 'normale');
        $img->save(Storage::path($cartella . '/' . $nomefile), 80);


        return $cartella . '/' . $nomefile;

    }

    protected function ridimensionaImmagine($img, $width, $height, $canvas, $testoLog)
    {
        //Resize immagine
        $img->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        //Aggiusta rapporto immagine
        Log::debug("Immagine $testoLog {$img->width()}x{$img->height()}");
        if ($canvas) {

            if ($img->height() < $height || $img->width() < $width) {
                \Log::debug('Aggiusto rapporto');
                $img->resizeCanvas($width, $height, 'center', false, 'ffffff');
            }
        }

        Log::debug("Immagine $testoLog finale: {$img->width()}x{$img->height()}");

        return $img;

    }


}
