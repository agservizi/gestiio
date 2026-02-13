<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\MieClassi\DatiRitorno;
use App\Models\ProduzioneOperatore;
use App\Models\TipoContratto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\FasciaListinoTipoContratto;
use DB;
use function App\getInputNumero;

class FasciaTipoContrattoController extends Controller
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
                'html' => base64_encode(view('Backend.FasciaListinoTipoContratto.tabella', [
                    'records' => $records,
                    'controller' => $nomeClasse,
                ]))
            ];

        }


        return view('Backend.FasciaListinoTipoContratto.index', [
            'records' => $records,
            'controller' => $nomeClasse,
            'titoloPagina' => 'Elenco ' . \App\Models\FasciaListinoTipoContratto::NOME_PLURALE,
            'orderBy' => $orderBy,
            'ordinamenti' => $ordinamenti,
            'filtro' => $filtro ?? 'tutti',
            'conFiltro' => $this->conFiltro,
            'testoNuovo' => 'Nuovo ' . \App\Models\FasciaListinoTipoContratto::NOME_SINGOLARE,
            'testoCerca' => null

        ]);


    }

    /** NOOOOOOO
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applicaFiltri($request)
    {

        $queryBuilder = \App\Models\FasciaListinoTipoContratto::query();
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
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($listinoId, $tipoContrattoId)
    {

        $tipoContratto=TipoContratto::find($tipoContrattoId);
        return view('Backend.FasciaListinoTipoContratto.edit', [
            'controller' => get_class($this),
            'titoloPagina' => 'Modifica fasce '.$tipoContratto->nome,
            'agenteId' => $listinoId,
            'tipoContrattoId' => $tipoContrattoId,
            'records' => FasciaListinoTipoContratto::where('listino_id', $listinoId)->where('tipo_contratto_id', $tipoContrattoId)->get()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $listinoId, $tipoContrattoId)
    {
        $datiCambiati = false;
        $datiCambiati = $datiCambiati || $this->sincronizzaSoglieOrdini($listinoId, $tipoContrattoId, $request->input('fasce'));


        if ($datiCambiati) {
            $this->impostaA($listinoId, $tipoContrattoId);
            $this->impostaImportoSogliePrecedenti($listinoId, $tipoContrattoId);
        }

        $produzione = ProduzioneOperatore::findByIdAnnoMese($listinoId, now()->year, now()->month);
        if ($produzione) {
            $produzione->ricalcola();
        }

        $datiRitorno = new DatiRitorno();
        $datiRitorno->chiudiDialog(true);
        return $datiRitorno->getArray();
    }


    /**
     * @param FasciaListinoTipoContratto $model
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
            'mandato_id' => '',
            'da_contratti' => '',
            'a_contratti' => '',
            'importo_per_ordine' => 'app\getInputNumero',
            'importo_bonus' => 'app\getInputNumero',
            'importo_soglie_precedenti' => 'app\getInputNumero',
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
        return \App\Models\FasciaListinoTipoContratto::get();
    }


    protected function rules($id = null)
    {


        $rules = [
            'mandato_id' => ['required'],
            'da_contratti' => ['required'],
            'a_contratti' => ['nullable'],
            'importo_per_ordine' => ['nullable'],
            'importo_bonus' => ['nullable'],
            'importo_soglie_precedenti' => ['nullable'],
        ];

        return $rules;
    }


    /**
     * @param int $gruppoId
     * @param array $voci
     * @return boolean
     */
    protected function sincronizzaSoglieOrdini($listinoId, $tipoContrattoId, $voci)
    {
        $datiCambiati = false;
        $esistenti = FasciaListinoTipoContratto::query()
            ->where('listino_id', $listinoId)
            ->where('tipo_contratto_id', $tipoContrattoId)
            ->get()->keyBy('id');

        $arrayVociVarianteEsistenti = $esistenti->pluck('id')->toArray();
        $arrayVociVariante = [];
        foreach ($voci as $voce) {
            if ($voce['da_contratti'] == null) continue;
            if (isset($voce['id'])) {
                //Esistente
                $arrayVociVariante[] = $voce['id'];
                $voceVariante = $esistenti[$voce['id']];
            } else {
                //Nuova
                $voceVariante = new FasciaListinoTipoContratto();
                $voceVariante->listino_id = $listinoId;
                $voceVariante->tipo_contratto_id = $tipoContrattoId;
            }
            $voceVariante->da_contratti = getInputNumero($voce['da_contratti']);
            $voceVariante->importo_per_contratto = getInputNumero($voce['importo_per_contratto']);
            $voceVariante->importo_bonus = getInputNumero($voce['importo_bonus']);
            $voceVariante->save();
            if ($voceVariante->wasChanged()) {
                $datiCambiati = true;
            }
        }
        $arrayEliminare = array_diff($arrayVociVarianteEsistenti, $arrayVociVariante);
        if (count($arrayEliminare)) {
            FasciaListinoTipoContratto::destroy($arrayEliminare);
            $datiCambiati = true;
        }
        return $datiCambiati;
    }

    public function impostaA($listinoId, $tipoContrattoId)
    {
        //Ordino per a_ordini
        $soglie = FasciaListinoTipoContratto::query()
            ->where('listino_id', $listinoId)
            ->where('tipo_contratto_id', $tipoContrattoId)
            ->orderBy('da_contratti', 'desc')
            ->get();
        $conteggio = 0;
        foreach ($soglie as $soglia) {
            if ($conteggio == 0) {
                //ultimo record
                $soglia->a_contratti = null;

            } else {
                $soglia->a_contratti = $aOrdini;

            }
            $aOrdini = $soglia->da_contratti - 1;

            $soglia->save();
            $conteggio++;
        }
    }

    public function impostaImportoSogliePrecedenti($listinoId, $tipoContrattoId)
    {
        //Ordino per a_ordini
        $soglie = $soglie = FasciaListinoTipoContratto::query()
            ->where('listino_id', $listinoId)
            ->where('tipo_contratto_id', $tipoContrattoId)
            ->orderBy('da_contratti', 'desc')
            ->get();
        $conteggio = 0;
        $aordiniOld = 0;
        $importoOld = 0;
        foreach ($soglie as $soglia) {

            if ($conteggio == 0) {
                //ultimo record
                $soglia->importo_soglie_precedenti = 0;
                $numeroOrdini = $soglia->a_contratti;
                $importoOld = $numeroOrdini * $soglia->importo_per_contratto;
                //echo 'conteggio:' . $conteggio . ' importocumulo:' . $importoCumulo . '<br>';

            } else {
                $soglia->importo_soglie_precedenti = $importoOld;

                $numeroOrdini = $soglia->a_contratti - $aordiniOld;
                $importoQuestiOrdini = $numeroOrdini * $soglia->importo_per_contratto;

                $importoOld += $importoQuestiOrdini;

                //echo 'conteggio:' . $conteggio . ' $numeroOrdini:' . $numeroOrdini . ' importocumulo:' . $importoCumulo . ' importo_soglie_precedenti:' . $soglia->importo_soglie_precedenti . '<br>';


            }
            $soglia->save();
            $conteggio++;
            $aordiniOld = $soglia->a_contratti;
        }

    }


}
