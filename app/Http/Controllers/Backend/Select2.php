<?php

namespace App\Http\Controllers\Backend;

use App\Models\ClienteAssistenza;
use App\Models\Comune;
use App\Models\ContattoBrt;
use App\Models\ContrattoTelefonia;
use App\Models\Gestore;
use App\Models\GestoreAttivazioniSim;
use App\Models\OffertaSim;
use App\Models\ProdottoAssistenza;
use App\Models\Provincia;
use App\Models\TipoContratto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;

class Select2 extends Controller
{
    public function response(Request $request)
    {


        $querystring = $request->input();


        //Prende la prima chiave della querystring
        reset($querystring);
        $key = key($querystring);
        //

        $term = trim($request->input('term'));


        // $term=trim($term);
        switch ($key) {

            case 'ragione_spedizioni':

                $contatti = ContattoBrt::query()
                    ->whereRaw('ragione_sociale_destinatario like ?', "%$term%")
                    ->limit(10)->orderBy('ragione_sociale_destinatario')
                    ->with('provincia')->get();
                if ($contatti->count()) {
                    $html = view('Backend.SpedizioneBrt.ricercaLista', ['contatti' => $contatti]);
                } else {
                    $html = '';
                }

                return ['success' => true, 'term' => $term, 'html' => base64_encode($html), 'risultati' => $contatti->count()];

            case 'gestore_id':
                $qb = Gestore::query()
                    ->select('id', 'nome as text')
                    ->orderBy('nome');
                if ($term) {
                    $qb->where('nome', 'like', "%$term%");
                }

                return $qb->get();

            case 'gestore_sim_id':
                $qb = GestoreAttivazioniSim::query()
                    ->select('id', 'nome as text')
                    ->orderBy('nome');
                if ($term) {
                    $qb->where('nome', 'like', "%$term%");
                }
                return $qb->get();

            case 'contratto_id':
                $qb = ContrattoTelefonia::limit(50)
                    ->orderByDesc('created_at')
                    ->with(['tipoContratto' => function ($q) {
                        $q->select('id', 'gestore_id', 'nome');
                    }]);
                if ($term) {
                    $arrTerm = explode(' ', $term);
                    foreach ($arrTerm as $t) {
                        $qb->where(DB::raw('concat_ws(\' \',codice_cliente,codice_contratto,nome,cognome,email,telefono)'), 'like', "%$t%");
                    }
                }
                $arr = [];
                foreach ($qb->get() as $record) {
                    $arr[] = ['id' => $record->id, 'text' => $record->nominativo() . ' - ' . $record->tipoContratto->nome];
                }
                return $arr;

            case 'agente_id':
                $qb = User::query()
                    ->where('id', '>', 1)
                    ->select('id', DB::raw('alias  as text'))
                    ->orderBy('alias')
                    ->has('permissions')
                    ->whereRaw('CONCAT_WS(" ",cognome,nome,alias) like ?', "%$term%");
                return $qb->get();

            case 'tipo_contratto_id':
                $qb = TipoContratto::query()
                    ->soloAttivi()
                    ->select('id', DB::raw('nome as text'))
                    ->orderBy('nome');
                if ($term) {
                    $qb->where('nome', 'like', "%$term%");
                }
                if ($request->input('agente_id')) {
                    $qb->whereHas('mandati', function ($q) use ($request) {
                        $q->where('agente_id', $request->input('agente_id'))->where('attivo', 1);
                    });
                }
                return $qb->get();

            case 'offerta_sim_id':
                $qb = OffertaSim::query()
                    ->select('id', DB::raw('nome as text'))
                    ->orderBy('nome');
                if ($term) {
                    $qb->where('nome', 'like', "%$term%");
                }
                if ($request->input('gestore_id')) {
                    $qb->whereHas('gestore', function ($q) use ($request) {
                        $q->where('gestore_id', $request->input('gestore_id'));
                    });
                }
                return $qb->get();

            case 'prodotto_assistenza':
                $qb = ProdottoAssistenza::query()
                    ->select('id', DB::raw('nome as text'))
                    ->orderBy('nome');
                if ($term) {
                    $qb->where('nome', 'like', "%$term%");
                }
                return $qb->get();

            case 'cliente_assistenza':
                $qb = ClienteAssistenza::query()
                    ->select('id', DB::raw('CONCAT_WS(" ",cognome,nome,codice_fiscale) as text'))
                    ->orderBy('cognome')->orderBy('nome')
                    ->whereRaw('CONCAT_WS(" ",cognome,nome,codice_fiscale) like ?', "%$term%");
                return $qb->get();


            case 'dati-cf':
                $codiceFiscale = $request->input('codice_fiscale');
                $datiRitorno = [];
                $parserCodiceFiscale = new CodiceFiscale();
                if ($parserCodiceFiscale->parse($codiceFiscale) !== false) {
                    $datiRitorno['genere'] = $parserCodiceFiscale->getGender();
                    $datiRitorno['data_di_nascita'] = $parserCodiceFiscale->getBirthdate()->format('d/m/Y');
                    $luogoNascita = $parserCodiceFiscale->getBirthPlace();
                    $cittaNascita = Comune::where('codice_catastale', $luogoNascita)->first();
                    if ($cittaNascita) {
                        $datiRitorno['luogo_di_nascita'] = $cittaNascita->comune;
                    } else {
                        $datiRitorno['luogo_di_nascita'] = $parserCodiceFiscale->getBirthPlaceComplete();
                    }
                }

                return ['success' => true, 'dati_ritorno' => $datiRitorno];


            case 'citta':
                if (empty($term)) {
                    return [''];
                }
                if (is_array($term)) {
                    $term = $term['term'];
                }


                $queryBuilder = Comune::orderBy('comune')->select(['elenco_comuni.id', DB::raw('CONCAT(comune, " (", targa,")") AS text'), 'cap']);
                return $queryBuilder->where('comune', 'like', $term . '%')->where('soppresso', 0)->get();
                break;


            case 'provincia':
                if (empty($term)) {
                    return [''];
                }
                return Provincia::orderBy('provincia')->select(['id', 'provincia as text'])->where('provincia', 'like', $term . '%')->get();


            case 'provincia_destinatario':
                if (empty($term)) {
                    return [''];
                }

                return DB::table('elenco_province')
                    ->select('sigla_automobilistica as id', 'provincia as text')
                    ->orderBy('provincia')
                    ->where('provincia', 'like', $term . '%')
                    ->get();


            case 'regione':
                $queryBuilder = Provincia::orderBy('regione')->select(['id_regione as id', 'regione as text']);
                if ($term != '') {
                    $queryBuilder->where('regione', 'like', $term . '%');
                }
                return $queryBuilder->distinct()->get();

            case 'nazione':
                if (empty($term)) {
                    return [''];
                }
                return DB::table('elenco_nazioni')
                    ->select('alpha2 as id', 'langit as text')
                    ->orderBy('langit')
                    ->where('langit', 'like', $term . '%')
                    ->get();

            case 'nazione_brt':
                if (empty($term)) {
                    return [''];
                }
                return DB::table('brt_nazioni_europa')
                    ->select('id', 'nome_nazione as text')
                    ->orderBy('nome_nazione')
                    ->where('nome_nazione', 'like', $term . '%')
                    ->get();


            default:

                return [];

        }


    }
}
