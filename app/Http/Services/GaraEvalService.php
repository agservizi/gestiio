<?php

namespace App\Http\Services;

use App\Models\Agente;
use App\Models\ContrattoEnergia;
use App\Models\ContrattoTelefonia;
use App\Models\Gara;
use App\Models\GaraRisultato;
use App\Models\GaraSoglia;
use App\Models\ProduzioneOperatore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

use function meseStrPad;

class GaraEvalService
{
    /**
     * Ricalcola premi gara per mese (tutti o un solo affiliato).
     *
     * @return array{updated:int,skipped:int}
     */
    public function ricalcolaMese(int $anno, int $mese, ?int $userId = null): array
    {
        if (! Schema::hasTable('gare') || ! Schema::hasTable('gara_risultati')) {
            return ['updated' => 0, 'skipped' => 0];
        }

        $gare = Gara::query()
            ->attive()
            ->delMese($anno, $mese)
            ->with(['soglie', 'agenti:id'])
            ->get();

        $updated = 0;
        $skipped = 0;

        foreach ($gare as $gara) {
            $userIds = $this->userIdsPerGara($gara, $userId);
            foreach ($userIds as $uid) {
                $uid = (int) $uid;
                if ($uid <= 0) {
                    continue;
                }

                $esistente = GaraRisultato::query()
                    ->where('gara_id', $gara->id)
                    ->where('user_id', $uid)
                    ->first();

                if ($esistente && $esistente->isCongelato()) {
                    $skipped++;
                    continue;
                }

                // Congela anche se la produzione mese ha già proforma
                $produzione = ProduzioneOperatore::find(ProduzioneOperatore::creaId($uid, $anno, $mese));
                if ($produzione && $produzione->fattura_proforma_id) {
                    $skipped++;
                    continue;
                }

                $valore = $this->contaContratti($gara, $uid, $anno, $mese);
                $soglia = $this->sogliaPerValore($gara->soglie, $valore);
                $premio = $soglia ? (float) $soglia->premio : 0.0;

                if ($valore <= 0 && $premio <= 0 && ! $esistente) {
                    continue;
                }

                GaraRisultato::query()->updateOrCreate(
                    ['gara_id' => $gara->id, 'user_id' => $uid],
                    [
                        'anno' => $anno,
                        'mese' => $mese,
                        'valore_raggiunto' => $valore,
                        'gara_soglia_id' => $soglia?->id,
                        'premio' => $premio,
                    ]
                );
                $updated++;
            }
        }

        return ['updated' => $updated, 'skipped' => $skipped];
    }

    /**
     * Somma premi gara del mese per un affiliato (anche già in proforma).
     */
    public function importoGareUser(int $userId, int $anno, int $mese): float
    {
        if (! Schema::hasTable('gara_risultati')) {
            return 0.0;
        }

        return round((float) GaraRisultato::query()
            ->delMese($anno, $mese)
            ->perUser($userId)
            ->sum('premio'), 2);
    }

    /**
     * Premi ancora da collegare a proforma (per componenti proforma).
     *
     * @return Collection<int, GaraRisultato>
     */
    public function risultatiAperti(int $userId, int $anno, int $mese): Collection
    {
        if (! Schema::hasTable('gara_risultati')) {
            return collect();
        }

        return GaraRisultato::query()
            ->with('gara:id,nome')
            ->delMese($anno, $mese)
            ->perUser($userId)
            ->whereNull('fattura_proforma_id')
            ->where('premio', '>', 0)
            ->get();
    }

    /**
     * @return list<int>
     */
    protected function userIdsPerGara(Gara $gara, ?int $soloUserId): array
    {
        if ($soloUserId) {
            if (! $this->userInAmbito($gara, $soloUserId)) {
                return [];
            }

            return [$soloUserId];
        }

        if ($gara->ambito === 'agenti') {
            return $gara->agenti->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        if ($gara->ambito === 'listino' && $gara->listino_id) {
            return Agente::query()
                ->where('listino_telefonia_id', $gara->listino_id)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        // tutti: affiliati con contratti del mese o già con risultato
        $meseKey = meseStrPad($gara->mese).'_'.$gara->anno;
        $ids = collect();
        if ($gara->canale === 'telefonia') {
            $ids = $ids->merge(
                ContrattoTelefonia::withoutGlobalScope('filtroOperatore')
                    ->daLiquidare()
                    ->where('mese_pagamento', $meseKey)
                    ->distinct()
                    ->pluck('agente_id')
            );
        } else {
            $ids = $ids->merge(
                ContrattoEnergia::withoutGlobalScope('filtroOperatore')
                    ->daLiquidare()
                    ->where('mese_pagamento', $meseKey)
                    ->distinct()
                    ->pluck('agente_id')
            );
        }
        $ids = $ids->merge(
            GaraRisultato::query()->where('gara_id', $gara->id)->pluck('user_id')
        );

        return $ids->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }

    protected function userInAmbito(Gara $gara, int $userId): bool
    {
        if ($gara->ambito === 'tutti') {
            return true;
        }
        if ($gara->ambito === 'agenti') {
            return $gara->agenti->contains('id', $userId);
        }
        if ($gara->ambito === 'listino' && $gara->listino_id) {
            return Agente::query()
                ->where('user_id', $userId)
                ->where('listino_telefonia_id', $gara->listino_id)
                ->exists();
        }

        return true;
    }

    protected function contaContratti(Gara $gara, int $userId, int $anno, int $mese): int
    {
        $meseKey = meseStrPad($mese).'_'.$anno;

        if ($gara->canale === 'telefonia') {
            $q = ContrattoTelefonia::withoutGlobalScope('filtroOperatore')
                ->daLiquidare()
                ->where('agente_id', $userId)
                ->where('mese_pagamento', $meseKey);

            if ($gara->telefonia_offerta_id) {
                $q->where('telefonia_offerta_id', $gara->telefonia_offerta_id);
            } elseif ($gara->tipo_contratto_id) {
                $q->where('tipo_contratto_id', $gara->tipo_contratto_id);
            }

            if ($gara->gestore_id) {
                $q->whereHas('tipoContratto', fn ($qq) => $qq->where('gestore_id', $gara->gestore_id));
            }

            if ($gara->is_business !== null && Schema::hasColumn('contratti', 'categoria_pratica')) {
                $q->where('categoria_pratica', $gara->is_business ? 'business' : 'consumer');
            }

            return (int) $q->count();
        }

        $q = ContrattoEnergia::withoutGlobalScope('filtroOperatore')
            ->daLiquidare()
            ->where('agente_id', $userId)
            ->where('mese_pagamento', $meseKey);

        if ($gara->gestore_id) {
            $q->where('gestore_id', $gara->gestore_id);
        }

        if ($gara->tipo_attivazione) {
            $q->where('tipo_attivazione', $gara->tipo_attivazione);
        }

        // Energia: is_business opzionale via gestore supporta_* se non c'è colonna sul contratto
        if ($gara->is_business !== null) {
            if (Schema::hasColumn((new ContrattoEnergia)->getTable(), 'categoria_pratica')) {
                $q->where('categoria_pratica', $gara->is_business ? 'business' : 'consumer');
            } else {
                $q->whereHas('gestore', function ($qq) use ($gara) {
                    if ($gara->is_business) {
                        $qq->where('supporta_business', 1);
                    } else {
                        $qq->where('supporta_consumer', 1);
                    }
                });
            }
        }

        return (int) $q->count();
    }

    /**
     * @param  Collection<int, GaraSoglia>  $soglie
     */
    protected function sogliaPerValore(Collection $soglie, int $valore): ?GaraSoglia
    {
        if ($valore <= 0 || $soglie->isEmpty()) {
            return null;
        }

        return $soglie
            ->filter(fn (GaraSoglia $s) => $s->matchValore($valore))
            ->sortByDesc('da_valore')
            ->first();
    }
}
