<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Funzioni\IbanGenerator;
use App\Http\Services\BrtTariffaService;
use App\Models\AbiCab;
use App\Models\Cliente;
use App\Models\Comune;
use App\Models\ContrattoTelefonia;
use App\Models\ElencoComuni;
use App\Models\ListinoBrt;
use App\Models\ListinoBrtEuropa;
use App\Models\NazioneEuropaBrt;
use App\Models\TipoContratto;
use App\Rules\CodiceFiscaleRule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use robertogallea\LaravelCodiceFiscale\CodiceFiscale;
use function App\getInputNumero;
use function App\getInputTelefono;
use function App\importo;

class AjaxController extends Controller
{
    public function post(Request $request, $cosa)
    {
        switch ($cosa) {

            case 'aggiorna-prezzo-brt':
                $contrassegno = getInputNumero($request->input('contrassegno'));
                $pesoTotale = $request->input('peso_totale');
                $colli = $request->input('numero_pacchi');
                $pudo = $request->input('pudo_id');
                $nazione = $request->input('nazione_destinazione');
                $service = new BrtTariffaService($nazione, $pesoTotale, $colli, $pudo, $contrassegno);
                if ($pesoTotale) {
                    $service->calcola();
                    if ($service->isError()) {
                        return ['success' => false, 'message' => $service->isError()];
                    }

                }

                return ['success' => true, 'prezzo' => importo($service->getPrezzo(), true), 'contrassegno' => importo($contrassegno, true), 'tariffa' => $service->getTestotariffa(), 'colli' => $colli];


            case 'genera-iban':
                $abiCab = AbiCab::inRandomOrder()->first();
                $numeroConto = Str::padLeft(rand(11111111111, 99999999999), 12, '0');
                $ibanGenerator = new IbanGenerator($abiCab->abi, $abiCab->cab, $numeroConto);
                return ['success' => true, 'iban' => $ibanGenerator->generate()];

            case 'controlla-email-telefonia':
                $tipoContratto = TipoContratto::with('gestore')->find($request->input('tipo_contratto_id'));
                $email = strtolower($request->input('email'));
                $esiste = ContrattoTelefonia::whereRelation('tipoContratto', 'gestore_id', $tipoContratto->gestore->id)->where('email', $email)->exists();
                if ($esiste) {
                    return ['success' => true, 'message' => 'Questo indirizzo email ha già stipulato un contratto con ' . $tipoContratto->gestore->nome];
                } else {
                    return ['success' => false];
                }

            case 'controlla-telefono-telefonia':

                $tipoContratto = TipoContratto::with('gestore')->find($request->input('tipo_contratto_id'));
                $telefono = getInputTelefono($request->input('telefono'));
                $esiste = ContrattoTelefonia::whereRelation('tipoContratto', 'gestore_id', $tipoContratto->gestore->id)->where('telefono', $telefono)->exists();
                if ($esiste) {
                    return ['success' => true, 'message' => 'Questo numero di telefono ha già stipulato un contratto con ' . $tipoContratto->gestore->nome];
                } else {
                    return ['success' => false];
                }

            case 'cliente-cf':
                $codiceFiscale = strtoupper($request->input('codice_fiscale'));
                $validator = \Validator::make(['codice_fiscale' => $codiceFiscale], ['codice_fiscale' => new CodiceFiscaleRule()]);
                if ($validator->fails()) {
                    return ['success' => false, 'message' => $validator->messages()->first('codice_fiscale')];
                }

                $record = Cliente::with('comune')->where('codice_fiscale', $codiceFiscale)->first();

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

                if ($record) {
                    return ['success' => true, 'cliente' => $record, 'dati_ritorno' => $datiRitorno];
                } else {
                    return ['success' => true, 'cliente' => null, 'dati_ritorno' => $datiRitorno];
                }

        }
    }
}
