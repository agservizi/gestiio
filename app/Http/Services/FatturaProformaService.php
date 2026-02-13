<?php

namespace App\Http\Services;

use App\Models\ContrattoTelefonia;
use App\Models\ContrattoEnergia;
use App\Models\FatturaProforma;
use App\Models\IntestazioneFatturaProforma;
use App\Models\ProduzioneOperatore;
use App\Models\RigaFatturaProforma;
use App\Models\Segnalazione;
use App\Models\User;
use Carbon\Carbon;

class FatturaProformaService
{
    protected $periodo;

    protected $error;

    public function __construct(protected $anno, protected $mese)
    {
        $this->periodo = $anno . '_' . $mese;
    }


    public function creaFattureProformaTutti(): int
    {
        $agenti = User::whereHas('permissions', function ($q) {
            $q->where('name', 'agente');
        })->get();

        $conteggio = 0;
        foreach ($agenti as $agente) {
            if ($this->creaFatturaProformaAgente($agente->id)) {
                $conteggio++;
            }
        }

        return $conteggio;
    }


    public function creaFatturaProformaAgente($agenteId): int|false
    {

        $produzione = ProduzioneOperatore::findOrNewMio($agenteId, $this->anno, $this->mese);

        if ($produzione->fattura_proforma_id) {
            $this->error = 'Fattura proforma già esistente';
            return false;
        }

        $produzione->user_id = $agenteId;
        $produzione->anno = $this->anno;
        $produzione->mese = $this->mese;

        $periodo = Carbon::createFromDate($this->anno, $this->mese, 1)->translatedFormat('F Y');

        $intestazione = IntestazioneFatturaProforma::firstWhere('user_id', $agenteId);
        if (!$intestazione) {
            $user = User::find($agenteId);
            $intestazione = new IntestazioneFatturaProforma();
            $intestazione->user_id = $agenteId;
            $intestazione->denominazione = $user->nominativo();
            $intestazione->codice_fiscale = $user->codice_fiscale;
            $intestazione->indirizzo = '';
            $intestazione->citta = '';
            $intestazione->cap = '';
            $intestazione->save();
        }

        \DB::beginTransaction();
        $fattura = new FatturaProforma();
        $fattura->data = now();
        $fattura->numero = $this->trovaNumero($fattura->data);
        $fattura->intestazione_id = $intestazione->id;
        $fattura->aliquota_iva = 0;
        $fattura->save();

        $totaleImponibile = 0;


        //Riga contratti telefonia
        $mesePagamento = str_pad($this->mese, 2, '0', STR_PAD_LEFT) . '_' . $this->anno;
        $conteggio = ContrattoTelefonia::withoutGlobalScope('filtroOperatore')->where('agente_id', $agenteId)->where('mese_pagamento', $mesePagamento)->count();
        ContrattoTelefonia::withoutGlobalScope('filtroOperatore')->where('agente_id', $agenteId)->where('mese_pagamento', $mesePagamento)->update(['fattura_proforma_id' => $fattura->id]);


        $riga = new RigaFatturaProforma();
        $riga->fattura_proforma_id = $fattura->id;
        $riga->descrizione = 'Provvigioni contratti Telefonia ' . $periodo;
        $riga->imponibile = $produzione->importo_ordini ?? 0;
        $riga->quantita = $conteggio;
        $riga->totale_imponibile = $riga->imponibile;
        $riga->classe = ContrattoTelefonia::class;

        $contratti = ContrattoTelefonia::where('fattura_proforma_id', $fattura->id)->with('tipoContratto')->get();
        $testo = [];
        foreach ($contratti as $contratto) {
            $testo[] = $contratto->nominativo() . ' - ' . $contratto->tipoContratto->nome;
        }
        if (count($testo)) {
            $riga->dettaglio = implode(', ', $testo);
        }
        $riga->save();

        $totaleImponibile += $riga->imponibile;

        //Riga energia
        $conteggio = ContrattoEnergia::withoutGlobalScope('filtroOperatore')->where('agente_id', $agenteId)->where('mese_pagamento', $mesePagamento)->count();
        ContrattoEnergia::withoutGlobalScope('filtroOperatore')->where('agente_id', $agenteId)->where('mese_pagamento', $mesePagamento)->update(['fattura_proforma_id' => $fattura->id]);

        $riga = new RigaFatturaProforma();
        $riga->fattura_proforma_id = $fattura->id;
        $riga->descrizione = 'Provvigioni contratti Energia ' . $periodo;
        $riga->imponibile = $produzione->importo_contratti_energia ?? 0;
        $riga->quantita = $conteggio;
        $riga->totale_imponibile = $riga->imponibile;
        $riga->classe = ContrattoEnergia::class;
        $contratti = ContrattoEnergia::where('fattura_proforma_id', $fattura->id)->with('gestore')->get();
        $testo = [];
        foreach ($contratti as $contratto) {
            $testo[] = $contratto->nominativo() . ' - ' . $contratto->gestore->nome;
        }
        if (count($testo)) {
            $riga->dettaglio = implode(', ', $testo);
        }
        $riga->save();
        $totaleImponibile += $riga->imponibile;


        //Riga seganalazione AMEX
        $dataInizio = Carbon::createFromDate($this->anno, $this->mese, 1);
        $dataFine = $dataInizio->copy()->endOfMonth();

        $conteggio = Segnalazione::withoutGlobalScope('filtroOperatore')
            ->whereDate('created_at', '>=', $dataInizio)
            ->whereDate('created_at', '<=', $dataFine)
            ->where('agente_id', $agenteId)
            ->where('esito_id', 'gestito')
            ->count();


        $riga = new RigaFatturaProforma();
        $riga->fattura_proforma_id = $fattura->id;
        $riga->descrizione = 'Provvigioni segnalazioni AMEX ' . $periodo;
        $riga->imponibile = $produzione->importo_segnalazioni ?? 0;
        $riga->quantita = $conteggio;
        $riga->totale_imponibile = $produzione->importo_segnalazioni ?? 0;
        $riga->classe = Segnalazione::class;
        $riga->save();
        $totaleImponibile += $riga->imponibile;

        $fattura->totale_imponibile = $totaleImponibile;
        $fattura->save();

        $produzione->fattura_proforma_id = $fattura->id;
        $produzione->save();

        \DB::commit();

        return $fattura->id;
    }

    public function getErrore()
    {
        return $this->error;
    }

    protected function trovaNumero($data)
    {
        $numero = FatturaProforma::whereYear('data', $data->year)->max('numero');
        if (!$numero) {
            $numero = 0;
        }
        return $numero + 1;
    }
}
