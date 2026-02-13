<?php

namespace App\Http\Services;

use App\Models\ChiamataApi;
use App\Models\ListinoBrt;
use App\Models\ListinoBrtEuropa;
use App\Models\NazioneEuropaBrt;
use App\Models\SpedizioneBrt;
use Illuminate\Support\Facades\Http;
use function App\applicaPercentuale;

class BrtTariffaService
{

    protected $prezzo;
    protected $testotariffa;

    protected $error = false;

    public function __construct(protected $nazione, protected $pesoTotale, protected $colli, protected $pudo = null, protected $contrassegno = null)
    {

    }

    public function calcola()
    {

        if ($this->nazione == 'IT') {
            $tariffa = ListinoBrt::trovaTariffa($this->pesoTotale);

            if ($tariffa) {
                $this->prezzo = $tariffa->calcolaPrezzo($this->pesoTotale, $this->pudo);
            }

            if ($this->contrassegno) {
                $this->prezzo =$this->prezzo+ $tariffa->contrassegno;
            }

            $this->testotariffa = 'Italia';
        } else {
            //Trovo gruppo
            $nazioneEuropaBrt = NazioneEuropaBrt::find($this->nazione);
            if (!$nazioneEuropaBrt) {
                \Log::alert("Nazione {$this->nazione} non trovata");
                $this->error = "Nazione {$this->nazione} non trovata";
            }
            $tariffa = ListinoBrtEuropa::trovaTariffa($this->pesoTotale, $this->colli > 1 ? 'multi' : 'mono');
            if (!$tariffa) {
                $this->error = 'Tariffa non trovata';
                return;
            }

            if ($this->colli > 1) {
                $this->testotariffa = 'Multicollo gruppo ' . $nazioneEuropaBrt->gruppo;
            } else {
                $this->testotariffa = 'Monocollo gruppo ' . $nazioneEuropaBrt->gruppo;
            }
            $this->prezzo = $tariffa->calcolaPrezzo(strtolower($nazioneEuropaBrt->gruppo));
        }

        if (\Auth::user()->hasPermissionTo('agente')) {
            $agente = \Auth::user()->agente()->select(['id', 'variazione_prezzi_spedizioni'])->first();
            if ($agente?->variazione_prezzi_spedizioni) {
                $this->prezzo = applicaPercentuale($this->prezzo, $agente->variazione_prezzi_spedizioni);
            }
        }

    }

    /**
     * @return mixed
     */
    public function getPrezzo()
    {
        return $this->prezzo;
    }

    public function isError(): bool
    {
        return $this->error;
    }

    /**
     * @return mixed
     */
    public function getTestotariffa()
    {
        return $this->testotariffa;
    }


}
