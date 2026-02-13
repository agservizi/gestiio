<?php

namespace App\Http\Livewire;

use App\Http\Controllers\Backend\AttivazioneSimController;
use App\Http\Controllers\Backend\CafPatronatoController;
use App\Http\Controllers\Backend\ClienteController;
use App\Http\Controllers\Backend\ContrattoTelefoniaController;
use App\Http\Controllers\Backend\ContrattoEnergiaController;
use App\Http\Controllers\Backend\ServizioFinanziarioController;
use App\Models\AttivazioneSim;
use App\Models\CafPatronato;
use App\Models\Cliente;
use App\Models\ContrattoEnergia;
use App\Models\ContrattoTelefonia;
use App\Models\ServizioFinanziario;
use DB;
use Livewire\Component;

class Ricerca extends Component
{
    public $testoRicerca;

    public $mostra = false;

    public $risultati = [];

    public function render()
    {
        if ($this->testoRicerca) {
            $this->mostra = true;
            $arrRisultati = [];

            //Cerco contratti
            $records = ContrattoTelefonia::with('tipoContratto:id,nome')
                ->where(DB::raw('concat_ws(\' \',nome,cognome,email,telefono,codice_fiscale,iban,codice_cliente,codice_contratto)'), 'like', "%{$this->testoRicerca}%")
                ->get();
            foreach ($records as $record) {
                $arrRisultati[] = ['testo' => $record->denominazione(), 'sottotesto' => 'Contratto ' . $record->tipoContratto->nome, 'url' => action([ContrattoTelefoniaController::class, 'show'], $record->id)];
            }

            $records = ContrattoEnergia::with('gestore:id,nome')
                ->where(DB::raw('concat_ws(\' \',testo_ricerca,email,telefono,codice_fiscale)'), 'like', "%{$this->testoRicerca}%")
                ->get();
            foreach ($records as $record) {
                $arrRisultati[] = ['testo' => $record->denominazione, 'sottotesto' => 'Contratto ' . $record->gestore->nome, 'url' => action([ContrattoEnergiaController::class, 'show'], $record->id)];
            }

            //Cerco servizio
            $records = ServizioFinanziario::where(DB::raw('concat_ws(\' \',nome,cognome,email,cellulare,codice_fiscale)'), 'like', "%{$this->testoRicerca}%")
                ->get();
            foreach ($records as $record) {
                $arrRisultati[] = ['testo' => $record->nominativo(), 'sottotesto' =>  $record->tipoProdottoBlade(), 'url' => action([ServizioFinanziarioController::class, 'edit'], $record->id)];
            }

            //Cerco CafPatronato
            $records = CafPatronato::with('tipo:id,nome')->where(DB::raw('concat_ws(\' \',nome,cognome,email,cellulare,codice_fiscale)'), 'like', "%{$this->testoRicerca}%")
                ->get();
            foreach ($records as $record) {
                $arrRisultati[] = ['testo' => $record->nominativo(), 'sottotesto' => 'Pratica ' . $record->tipo->nome, 'url' => action([CafPatronatoController::class, 'edit'], $record->id)];
            }

            //Cerco Attivazioni
            $records = AttivazioneSim::with('gestore:id,nome')->where(DB::raw('concat_ws(\' \',nome,cognome,email,cellulare,codice_fiscale)'), 'like', "%{$this->testoRicerca}%")
                ->get();
            foreach ($records as $record) {
                $arrRisultati[] = ['testo' => $record->nominativo(), 'sottotesto' => 'Attivazione sim ' . $record->gestore->nome, 'url' => action([AttivazioneSimController::class, 'edit'], $record->id)];
            }

            //Cerco clienti
            $records = Cliente::where(DB::raw('concat_ws(\' \',ragione_sociale,nome,cognome,email,telefono,codice_fiscale)'), 'like', "%{$this->testoRicerca}%")
                ->get();
            foreach ($records as $record) {
                $arrRisultati[] = ['testo' => $record->denominazione(), 'sottotesto' => 'Cliente', 'url' => action([ClienteController::class, 'show'], $record->id)];
            }

            $this->risultati = $arrRisultati;

        } else {
            $this->mostra = false;
        }

        return view('livewire.ricerca', [
            'risultati' => $this->risultati
        ]);
    }


}
