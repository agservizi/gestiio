<?php

namespace App\Http\Services;

use App\Enums\FatturaProformaStatus;
use App\Models\ContrattoEnergia;
use App\Models\ContrattoTelefonia;
use App\Models\FatturaProforma;
use App\Models\GaraRisultato;
use App\Models\ProduzioneOperatore;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

use function meseStrPad;

class LiquidazioneProvvigioniService
{
    public function __construct(protected int $anno, protected int $mese)
    {
    }

    /**
     * Righe liquidazione mese: un record per agente con produzione / proforma / contratti.
     *
     * @return Collection<int, object>
     */
    public function righeMese(): Collection
    {
        $meseKey = meseStrPad($this->mese).'_'.$this->anno;

        $produzioni = ProduzioneOperatore::query()
            ->with(['agente:id,nome,cognome,email', 'fatturaProforma'])
            ->where('anno', $this->anno)
            ->where('mese', $this->mese)
            ->get()
            ->keyBy('user_id');

        // Inclusi agenti con contratti da liquidare / premi gara anche senza riga produzione
        $agenteIds = $produzioni->keys()->merge(
            ContrattoTelefonia::withoutGlobalScope('filtroOperatore')
                ->daLiquidare()
                ->where('mese_pagamento', $meseKey)
                ->distinct()
                ->pluck('agente_id')
        )->merge(
            ContrattoEnergia::withoutGlobalScope('filtroOperatore')
                ->daLiquidare()
                ->where('mese_pagamento', $meseKey)
                ->distinct()
                ->pluck('agente_id')
        );
        if (Schema::hasTable('gara_risultati')) {
            $agenteIds = $agenteIds->merge(
                GaraRisultato::query()
                    ->delMese($this->anno, $this->mese)
                    ->where('premio', '>', 0)
                    ->distinct()
                    ->pluck('user_id')
            );
        }
        $agenteIds = $agenteIds->unique()->filter()->values();

        $gareByUser = Schema::hasTable('gara_risultati')
            ? GaraRisultato::query()
                ->delMese($this->anno, $this->mese)
                ->selectRaw('user_id, SUM(premio) as tot')
                ->groupBy('user_id')
                ->pluck('tot', 'user_id')
            : collect();

        $users = User::query()->whereIn('id', $agenteIds)->get(['id', 'nome', 'cognome', 'email'])->keyBy('id');

        return $agenteIds->map(function ($userId) use ($produzioni, $users, $meseKey, $gareByUser) {
            /** @var ProduzioneOperatore|null $p */
            $p = $produzioni->get($userId);
            $user = $users->get($userId);
            $telImp = (float) ($p->importo_ordini ?? 0);
            $enImp = (float) ($p->importo_contratti_energia ?? 0);
            $gareImp = round((float) ($gareByUser[$userId] ?? 0), 2);
            $totale = $telImp + $enImp + $gareImp;

            $telDaLiq = ContrattoTelefonia::withoutGlobalScope('filtroOperatore')
                ->daLiquidare()->where('agente_id', $userId)->where('mese_pagamento', $meseKey)->count();
            $enDaLiq = ContrattoEnergia::withoutGlobalScope('filtroOperatore')
                ->daLiquidare()->where('agente_id', $userId)->where('mese_pagamento', $meseKey)->count();
            $telPagati = ContrattoTelefonia::withoutGlobalScope('filtroOperatore')
                ->where('agente_id', $userId)->where('mese_pagamento', $meseKey)->where('pagato', 1)->count();
            $enPagati = ContrattoEnergia::withoutGlobalScope('filtroOperatore')
                ->where('agente_id', $userId)->where('mese_pagamento', $meseKey)->where('pagato', 1)->count();

            $proforma = $p?->fatturaProforma;
            // relazione hasOne su ProduzioneOperatore usa fattura_proforma_id
            if (! $proforma && $p?->fattura_proforma_id) {
                $proforma = FatturaProforma::withoutGlobalScopes()->find($p->fattura_proforma_id);
            }

            return (object) [
                'user_id' => (int) $userId,
                'nominativo' => $user ? $user->nominativo() : ('#'.$userId),
                'email' => $user?->email,
                'produzione_id' => $p?->id,
                'importo_telefonia' => $telImp,
                'importo_energia' => $enImp,
                'importo_gare' => $gareImp,
                'importo_totale' => $totale,
                'contratti_da_liquidare' => $telDaLiq + $enDaLiq,
                'contratti_pagati' => $telPagati + $enPagati,
                'proforma_id' => $proforma?->id,
                'proforma_numero' => $proforma?->numero,
                'proforma_status' => $proforma ? $proforma->statusEnum()->value : null,
                'proforma_label' => $proforma ? $proforma->statusLabel() : 'Nessuna',
                'proforma_badge' => $proforma ? $proforma->statusBadgeClass() : 'badge-light',
            ];
        })->sortBy('nominativo')->values();
    }

    /**
     * Riepilogo previsto / liquidabile / pagato.
     *
     * @return array{previsto:float,liquidabile:float,pagato:float,proforma_da_pagare:int,agenti:int}
     */
    public function riepilogo(): array
    {
        $righe = $this->righeMese();
        $previsto = $righe->sum('importo_totale');
        $liquidabile = $righe->filter(fn ($r) => ($r->proforma_status ?? '') !== FatturaProformaStatus::PAGATA->value)
            ->sum('importo_totale');
        $pagato = $righe->filter(fn ($r) => ($r->proforma_status ?? '') === FatturaProformaStatus::PAGATA->value)
            ->sum('importo_totale');
        $proformaDaPagare = $righe->filter(fn ($r) => $r->proforma_id
            && ($r->proforma_status ?? '') !== FatturaProformaStatus::PAGATA->value)->count();

        return [
            'previsto' => round((float) $previsto, 2),
            'liquidabile' => round((float) $liquidabile, 2),
            'pagato' => round((float) $pagato, 2),
            'proforma_da_pagare' => $proformaDaPagare,
            'agenti' => $righe->count(),
        ];
    }
}
