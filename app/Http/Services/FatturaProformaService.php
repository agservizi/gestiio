<?php

namespace App\Http\Services;

use App\Enums\FatturaProformaStatus;
use App\Http\Services\Billing\AgentProformaInvoiceShelfSync;
use App\Models\ContrattoEnergia;
use App\Models\ContrattoTelefonia;
use App\Models\FatturaProforma;
use App\Models\IntestazioneFatturaProforma;
use App\Models\ProduzioneOperatore;
use App\Models\RigaFatturaProforma;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

use function meseStrPad;

class FatturaProformaService
{
    protected $periodo;

    protected $error;

    public function __construct(protected $anno, protected $mese)
    {
        $this->periodo = $anno.'_'.meseStrPad($mese);
    }

    public function creaFattureProformaTutti(): int
    {
        $agenti = User::whereHas('permissions', function ($q) {
            $q->where('name', 'agente');
        })->get();

        $conteggio = 0;
        foreach ($agenti as $agente) {
            if ($this->creaFatturaProformaAgente($agente->id, FatturaProformaStatus::EMESSA)) {
                $conteggio++;
            }
        }

        return $conteggio;
    }

    /**
     * @return array{ok:bool,error?:string,produzione?:ProduzioneOperatore,intestazione?:IntestazioneFatturaProforma,periodo?:string,linee?:array,totale?:float,intestazione_incompleta?:bool}
     */
    public function previewAgente(int $agenteId): array
    {
        $produzione = ProduzioneOperatore::findOrNewMio($agenteId, $this->anno, $this->mese);

        if ($produzione->fattura_proforma_id) {
            return ['ok' => false, 'error' => 'Fattura proforma già esistente'];
        }

        $totale = (float) ($produzione->importo_totale ?? 0);
        if ($totale <= 0) {
            return ['ok' => false, 'error' => 'Importo zero: nessuna proforma'];
        }

        $intestazione = IntestazioneFatturaProforma::firstWhere('user_id', $agenteId);
        $periodo = Carbon::createFromDate($this->anno, $this->mese, 1)->translatedFormat('F Y');
        $mesePagamento = meseStrPad($this->mese).'_'.$this->anno;

        $linee = $this->buildLineePreview($produzione, $periodo, $agenteId, $mesePagamento);

        return [
            'ok' => true,
            'produzione' => $produzione,
            'intestazione' => $intestazione,
            'periodo' => $periodo,
            'linee' => $linee,
            'totale' => $totale,
            'intestazione_incompleta' => $this->isIntestazioneIncompleta($intestazione),
        ];
    }

    /**
     * @return array{id:int,numero:int}|false
     */
    public function creaFatturaProformaAgente($agenteId, FatturaProformaStatus $status = FatturaProformaStatus::BOZZA): array|false
    {
        $produzione = ProduzioneOperatore::findOrNewMio($agenteId, $this->anno, $this->mese);

        if ($produzione->fattura_proforma_id) {
            $this->error = 'Fattura proforma già esistente';

            return false;
        }

        $totaleProduzione = (float) ($produzione->importo_totale ?? 0);
        if ($totaleProduzione <= 0) {
            $this->error = 'Importo zero: nessuna proforma';

            return false;
        }

        $produzione->user_id = $agenteId;
        $produzione->anno = $this->anno;
        $produzione->mese = $this->mese;

        $intestazione = $this->ensureIntestazione($agenteId);

        try {
            DB::beginTransaction();

            $dataFattura = today();
            $fattura = new FatturaProforma;
            $fattura->data = $dataFattura;
            $fattura->numero = $this->trovaNumero($dataFattura);
            $fattura->intestazione_id = $intestazione->id;
            $fattura->aliquota_iva = 0;
            $fattura->status = $status->value;
            $fattura->save();

            $totaleImponibile = $this->scriviRigheECollegaContratti($fattura, $produzione, $agenteId);

            if ($totaleImponibile <= 0) {
                DB::rollBack();
                $this->error = 'Importo zero: nessuna proforma';

                return false;
            }

            $fattura->totale_imponibile = $totaleImponibile;
            $fattura->save();

            $produzione->fattura_proforma_id = $fattura->id;
            $produzione->save();

            DB::commit();

            $this->syncToInvoiceShelf($fattura->fresh(['righe', 'intestazione']));

            return ['id' => (int) $fattura->id, 'numero' => (int) $fattura->numero];
        } catch (Throwable $e) {
            DB::rollBack();
            \Log::error('FatturaProformaService::creaFatturaProformaAgente', [
                'message' => $e->getMessage(),
                'agente_id' => $agenteId,
            ]);
            $this->error = 'Errore creazione proforma. Riprova o contatta l\'assistenza.';

            return false;
        }
    }

    public function rigenera(FatturaProforma $fattura): bool
    {
        if (! $fattura->statusEnum()->canRegenerate()) {
            $this->error = 'Non rigenerabile: già pagata';

            return false;
        }

        $produzione = ProduzioneOperatore::firstWhere('fattura_proforma_id', $fattura->id);
        if (! $produzione) {
            $this->error = 'Produzione collegata non trovata';

            return false;
        }

        $this->anno = $produzione->anno;
        $this->mese = $produzione->mese;
        $this->periodo = $produzione->anno.'_'.meseStrPad($produzione->mese);

        try {
            DB::beginTransaction();

            $this->scollegaContratti($fattura->id);
            RigaFatturaProforma::where('fattura_proforma_id', $fattura->id)->delete();

            $totaleImponibile = $this->scriviRigheECollegaContratti($fattura, $produzione, $produzione->user_id);

            if ($totaleImponibile <= 0) {
                DB::rollBack();
                $this->error = 'Importo zero: impossibile rigenerare';

                return false;
            }

            $fattura->totale_imponibile = $totaleImponibile;
            $fattura->save();

            DB::commit();

            $this->syncToInvoiceShelf($fattura->fresh(['righe', 'intestazione']));

            return true;
        } catch (Throwable $e) {
            DB::rollBack();
            \Log::error('FatturaProformaService::rigenera', ['message' => $e->getMessage(), 'fattura_id' => $fattura->id]);
            $this->error = 'Errore rigenerazione. Riprova o contatta l\'assistenza.';

            return false;
        }
    }

    public function elimina(FatturaProforma $fattura): bool
    {
        if (! $fattura->statusEnum()->canDelete()) {
            $this->error = 'Non eliminabile: già pagata';

            return false;
        }

        try {
            DB::beginTransaction();

            $this->scollegaContratti($fattura->id);

            ProduzioneOperatore::where('fattura_proforma_id', $fattura->id)
                ->update(['fattura_proforma_id' => null]);

            RigaFatturaProforma::where('fattura_proforma_id', $fattura->id)->delete();
            $fattura->delete();

            DB::commit();

            return true;
        } catch (Throwable $e) {
            DB::rollBack();
            \Log::error('FatturaProformaService::elimina', ['message' => $e->getMessage(), 'fattura_id' => $fattura->id]);
            $this->error = 'Errore eliminazione. Riprova o contatta l\'assistenza.';

            return false;
        }
    }

    public function getErrore()
    {
        return $this->error;
    }

    protected function ensureIntestazione(int $agenteId): IntestazioneFatturaProforma
    {
        $intestazione = IntestazioneFatturaProforma::firstWhere('user_id', $agenteId);
        if ($intestazione) {
            return $intestazione;
        }

        $user = User::find($agenteId);
        $intestazione = new IntestazioneFatturaProforma;
        $intestazione->user_id = $agenteId;
        $intestazione->denominazione = $user ? $user->nominativo() : 'Agente #'.$agenteId;
        $intestazione->codice_fiscale = $user?->codice_fiscale ?? '';
        $intestazione->indirizzo = '';
        $intestazione->citta = '';
        $intestazione->cap = '';
        $intestazione->save();

        return $intestazione;
    }

    public function isIntestazioneIncompleta(?IntestazioneFatturaProforma $intestazione): bool
    {
        if (! $intestazione) {
            return true;
        }

        return trim((string) $intestazione->indirizzo) === ''
            || trim((string) $intestazione->citta) === ''
            || trim((string) $intestazione->cap) === ''
            || trim((string) $intestazione->codice_fiscale) === '';
    }

    protected function mesePagamento(): string
    {
        return meseStrPad($this->mese).'_'.$this->anno;
    }

    /**
     * @return list<array{descrizione:string,quantita:int,imponibile:float,dettaglio:?string}>
     */
    protected function buildLineePreview(ProduzioneOperatore $produzione, string $periodo, int $agenteId, string $mesePagamento): array
    {
        $linee = [];
        $componenti = $this->componentiImporti($produzione);

        foreach ($componenti as $comp) {
            if ($comp['importo'] <= 0) {
                continue;
            }
            $linee[] = [
                'descrizione' => $comp['descrizione'].' '.$periodo,
                'quantita' => 1,
                'imponibile' => $comp['importo'],
                'dettaglio' => $comp['dettaglio'] ?? null,
            ];
        }

        return $linee;
    }

    /**
     * @return list<array{key:string,descrizione:string,importo:float,quantita?:int,classe?:?string,dettaglio?:?string}>
     */
    protected function componentiImporti(ProduzioneOperatore $produzione): array
    {
        $agenteId = (int) $produzione->user_id;
        $mesePagamento = $this->mesePagamento();

        $telCount = ContrattoTelefonia::withoutGlobalScope('filtroOperatore')
            ->where('agente_id', $agenteId)
            ->where('mese_pagamento', $mesePagamento)
            ->count();

        $enCount = ContrattoEnergia::withoutGlobalScope('filtroOperatore')
            ->where('agente_id', $agenteId)
            ->where('mese_pagamento', $mesePagamento)
            ->count();

        return [
            [
                'key' => 'telefonia',
                'descrizione' => 'Provvigioni contratti Telefonia',
                'importo' => (float) ($produzione->importo_ordini ?? 0),
                'quantita' => max(1, $telCount),
                'classe' => ContrattoTelefonia::class,
            ],
            [
                'key' => 'energia',
                'descrizione' => 'Provvigioni contratti Energia',
                'importo' => (float) ($produzione->importo_contratti_energia ?? 0),
                'quantita' => max(1, $enCount),
                'classe' => ContrattoEnergia::class,
            ],
            [
                'key' => 'servizi_finanziari',
                'descrizione' => 'Provvigioni servizi finanziari',
                'importo' => (float) ($produzione->importo_servizi_finanziari ?? 0),
                'quantita' => 1,
                'classe' => null,
            ],
            [
                'key' => 'segnalazioni',
                'descrizione' => 'Provvigioni segnalazioni',
                'importo' => (float) ($produzione->importo_segnalazioni ?? 0),
                'quantita' => 1,
                'classe' => null,
            ],
            [
                'key' => 'sim',
                'descrizione' => 'Provvigioni attivazioni SIM',
                'importo' => (float) ($produzione->importo_attivazioni_sim ?? 0),
                'quantita' => 1,
                'classe' => null,
            ],
        ];
    }

    protected function scriviRigheECollegaContratti(FatturaProforma $fattura, ProduzioneOperatore $produzione, int $agenteId): float
    {
        $periodo = Carbon::createFromDate($this->anno, $this->mese, 1)->translatedFormat('F Y');
        $mesePagamento = $this->mesePagamento();
        $totaleImponibile = 0.0;

        foreach ($this->componentiImporti($produzione) as $comp) {
            if ($comp['importo'] <= 0) {
                continue;
            }

            if ($comp['classe'] === ContrattoTelefonia::class) {
                ContrattoTelefonia::withoutGlobalScope('filtroOperatore')
                    ->where('agente_id', $agenteId)
                    ->where('mese_pagamento', $mesePagamento)
                    ->update(['fattura_proforma_id' => $fattura->id]);

                $dettaglio = $this->dettaglioTelefonia($fattura->id);
            } elseif ($comp['classe'] === ContrattoEnergia::class) {
                ContrattoEnergia::withoutGlobalScope('filtroOperatore')
                    ->where('agente_id', $agenteId)
                    ->where('mese_pagamento', $mesePagamento)
                    ->update(['fattura_proforma_id' => $fattura->id]);

                $dettaglio = $this->dettaglioEnergia($fattura->id);
            } else {
                $dettaglio = null;
            }

            // quantita=1: imponibile è già il totale periodo
            $riga = new RigaFatturaProforma;
            $riga->fattura_proforma_id = $fattura->id;
            $riga->descrizione = $comp['descrizione'].' '.$periodo;
            $riga->imponibile = $comp['importo'];
            $riga->quantita = 1;
            $riga->totale_imponibile = $comp['importo'];
            $riga->classe = $comp['classe'];
            if ($dettaglio) {
                $riga->dettaglio = $dettaglio;
            }
            $riga->save();

            $totaleImponibile += $comp['importo'];
        }

        return $totaleImponibile;
    }

    protected function dettaglioTelefonia(int $fatturaId): ?string
    {
        $contratti = ContrattoTelefonia::withoutGlobalScope('filtroOperatore')
            ->where('fattura_proforma_id', $fatturaId)
            ->with('tipoContratto')
            ->get();
        $testo = [];
        foreach ($contratti as $contratto) {
            $tipo = optional($contratto->tipoContratto)->nome ?? '';
            $testo[] = trim($contratto->nominativo().' - '.$tipo);
        }

        return count($testo) ? implode(', ', $testo) : null;
    }

    protected function dettaglioEnergia(int $fatturaId): ?string
    {
        $contratti = ContrattoEnergia::withoutGlobalScope('filtroOperatore')
            ->where('fattura_proforma_id', $fatturaId)
            ->with('gestore')
            ->get();
        $testo = [];
        foreach ($contratti as $contratto) {
            $gestore = optional($contratto->gestore)->nome ?? '';
            $testo[] = trim($contratto->nominativo().' - '.$gestore);
        }

        return count($testo) ? implode(', ', $testo) : null;
    }

    protected function scollegaContratti(int $fatturaId): void
    {
        ContrattoTelefonia::withoutGlobalScope('filtroOperatore')
            ->where('fattura_proforma_id', $fatturaId)
            ->update(['fattura_proforma_id' => null]);

        ContrattoEnergia::withoutGlobalScope('filtroOperatore')
            ->where('fattura_proforma_id', $fatturaId)
            ->update(['fattura_proforma_id' => null]);
    }

    protected function trovaNumero($data): int
    {
        $numero = FatturaProforma::whereYear('data', $data->year)
            ->lockForUpdate()
            ->max('numero');
        if (! $numero) {
            $numero = 0;
        }

        return $numero + 1;
    }

    protected function syncToInvoiceShelf(FatturaProforma $fattura): void
    {
        try {
            app(AgentProformaInvoiceShelfSync::class)->sync($fattura, (int) $this->anno, (int) $this->mese);
        } catch (Throwable $e) {
            \Log::warning('FatturaProformaService sync InvoiceShelf', [
                'fattura_id' => $fattura->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
