<?php

namespace App\Http\Controllers\Frontend;

use App\Models\Comune;
use App\Models\Nazione;
use App\Models\Provincia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

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


            //Città

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
                abort(404, 'Select2 ' . $key . ' non supportato');

        }


    }
}
